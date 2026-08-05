<?php
// includes/db_connect.php
// Simple mysqli connection used across the whole BDMS site.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";        // set your MySQL root password here
$DB_NAME = "bdms";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Helper: generate a simple sequential ID like DNR001, RCV014, etc.
function generateId($conn, $table, $idColumn, $prefix) {
    $escapedPrefix = $conn->real_escape_string($prefix);
    $result = $conn->query("SELECT $idColumn FROM $table WHERE $idColumn LIKE '{$escapedPrefix}%' ORDER BY LENGTH($idColumn) DESC, $idColumn DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $last = $result->fetch_assoc()[$idColumn];
        $num = intval(substr($last, strlen($prefix))) + 1;
    } else {
        $num = 1;
    }
    return $prefix . str_pad($num, 3, "0", STR_PAD_LEFT);
}

// ---------------------------------------------------------------------
// Auth guards. Call at the top of any page that requires a logged-in
// role. $base should be "../" (pages live one folder below bdms/).
// ---------------------------------------------------------------------
function requireAdminLogin($base) {
    if (empty($_SESSION["admin_id"])) {
        header("Location: {$base}admin/login.php");
        exit;
    }
}

function requireIaLogin($base) {
    if (empty($_SESSION["ia_staff_id"])) {
        header("Location: {$base}ia/login.php");
        exit;
    }
}

function requireTaLogin($base) {
    if (empty($_SESSION["ta_staff_id"])) {
        header("Location: {$base}transport/login.php");
        exit;
    }
}

function requirePersonLogin($base) {
    if (empty($_SESSION["person_id"])) {
        header("Location: {$base}person/login.php");
        exit;
    }
}

// ---------------------------------------------------------------------
// Call this the moment a login is verified, BEFORE setting any new
// session keys. It wipes any leftover session data from a different
// role (admin/ia/ta/person) and issues a fresh session ID, so a
// browser can only ever be authenticated as ONE role at a time.
// Without this, logging into a second role while an old role's
// session keys are still present lets a user jump straight into that
// other role's pages (e.g. /admin/dashboard.php) without ever
// logging out of it first.
// ---------------------------------------------------------------------
function resetSessionForLogin() {
    $_SESSION = [];
    session_regenerate_id(true);
}

// Call this on every logout page instead of unset()-ing individual
// keys, so no other role's session data can linger behind.
function destroySession() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

/**
 * Automatic system-wide rebalancing.
 * Deficit  = a receiver is waiting at an IA (open blood_request) and that
 *            IA currently holds 0 units of the needed blood type.
 * Excess   = an IA holds stock of a blood type that nobody there is
 *            currently waiting on.
 * For every deficit, the system looks for the best-stocked excess IA with
 * the same blood type and immediately dispatches a transport assignment for
 * the matched amount (auto-picking the first available Transport Agency).
 * Any deficit left unmatched simply stays visible as an active campaign
 * (surfaced on the homepage and admin/campaigns.php) until a donor gives.
 * Returns an array of match descriptions for display.
 */
function runAutoMatch($conn) {
    $BLOOD_TYPES = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
    $matches = [];

    // ------------------------------------------------------------------
    // Phase 0: same-agency self-fulfillment.
    // If stock just landed at the SAME IA where a receiver is already
    // waiting (e.g. a donor gave blood at that IA after the request was
    // forwarded), that request should be cleared from local stock before
    // we even look at other agencies. Without this, an agency that has
    // both units_available > 0 AND an open request (has_demand = 1) is
    // excluded from the "sources" list below (because it still shows
    // demand) but no longer counts as a "deficit" either (because it's
    // no longer at 0 units) — so its own request is never resolved.
    // ------------------------------------------------------------------
    $stockRes = $conn->query("SELECT li.agency_id, ag.agency_name, li.blood_type, li.units_available
                               FROM local_inventory li JOIN agency ag ON li.agency_id = ag.agency_id
                               WHERE li.units_available > 0");
    $stockRows = $stockRes ? $stockRes->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($stockRows as $stock) {
        $agencyId = $stock['agency_id'];
        $bt = $stock['blood_type'];
        $avail = (int)$stock['units_available'];
        if ($avail <= 0) continue;

        $reqRes = $conn->query("SELECT * FROM blood_request
                                 WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "'
                                 AND blood_type = '" . $conn->real_escape_string($bt) . "'
                                 AND status IN ('PENDING','FORWARDED_TO_BDMS')
                                 ORDER BY request_date ASC");
        while ($avail > 0 && $req = $reqRes->fetch_assoc()) {
            $needed = (int)$req['units_requested'];
            if ($needed > $avail) break; // keep FIFO order intact; wait for enough stock

            $conn->begin_transaction();
            try {
                $conn->query("UPDATE blood_request SET status = 'FULFILLED_LOCALLY'
                              WHERE request_id = " . (int)$req['request_id']);
                $conn->query("UPDATE local_inventory SET units_available = units_available - $needed
                              WHERE agency_id = '" . $conn->real_escape_string($agencyId) . "' AND blood_type = '" . $conn->real_escape_string($bt) . "'");
                $conn->query("UPDATE central_inventory SET units_available = GREATEST(units_available - $needed, 0)
                              WHERE system_id = 'BDMS01' AND blood_type = '" . $conn->real_escape_string($bt) . "'");
                $conn->commit();

                $avail -= $needed;
                $matches[] = "$needed unit(s) of $bt released to a waiting receiver at {$stock['agency_name']} from newly available local stock.";
            } catch (Exception $e) {
                $conn->rollback();
            }
        }
    }

    $taRes = $conn->query("SELECT agency_id FROM agency WHERE agency_type = 'TA' ORDER BY agency_id LIMIT 1");
    $taAgencyId = $taRes && $taRes->num_rows > 0 ? $taRes->fetch_assoc()['agency_id'] : null;
    if (!$taAgencyId) return $matches; // no transporter available to dispatch anything

    $allStock = [];
    $res = $conn->query("SELECT li.agency_id, ag.agency_name, li.blood_type, li.units_available,
                                 EXISTS(
                                     SELECT 1 FROM blood_request br
                                     WHERE br.agency_id = li.agency_id AND br.blood_type = li.blood_type
                                     AND br.status IN ('PENDING','FORWARDED_TO_BDMS')
                                 ) AS has_demand
                          FROM local_inventory li JOIN agency ag ON li.agency_id = ag.agency_id");
    while ($row = $res->fetch_assoc()) {
        $allStock[$row['blood_type']][] = $row;
    }

    foreach ($BLOOD_TYPES as $bt) {
        if (empty($allStock[$bt])) continue;
        $rows = $allStock[$bt];
        $deficits = array_values(array_filter($rows, fn($r) => (int)$r['units_available'] === 0 && (int)$r['has_demand'] === 1));
        $sources  = array_values(array_filter($rows, fn($r) => (int)$r['units_available'] >= 1 && (int)$r['has_demand'] === 0));
        if (empty($deficits) || empty($sources)) continue;

        usort($sources, fn($a, $b) => $b['units_available'] <=> $a['units_available']);
        $sourceIdx = 0;

        foreach ($deficits as $def) {
            while ($sourceIdx < count($sources) && (
                    $sources[$sourceIdx]['agency_id'] === $def['agency_id'] ||
                    $sources[$sourceIdx]['units_available'] < 1
                  )) {
                $sourceIdx++;
            }
            if ($sourceIdx >= count($sources)) break;

            $src = $sources[$sourceIdx];
            $units = max(1, intdiv((int)$src['units_available'], 2));
            $units = min($units, (int)$src['units_available']);

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO transport_assignment (system_id, ta_agency_id, source_agency_id, dest_agency_id, blood_type, units)
                                         VALUES ('BDMS01', ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $taAgencyId, $src['agency_id'], $def['agency_id'], $bt, $units);
                $stmt->execute();
                $stmt->close();

                $conn->query("UPDATE local_inventory SET units_available = units_available - $units
                              WHERE agency_id = '" . $conn->real_escape_string($src['agency_id']) . "' AND blood_type = '" . $conn->real_escape_string($bt) . "'");
                $conn->commit();

                $matches[] = "$units unit(s) of $bt matched from {$src['agency_name']} to {$def['agency_name']} and dispatched.";
                $sources[$sourceIdx]['units_available'] -= $units;
            } catch (Exception $e) {
                $conn->rollback();
            }
        }
    }

    return $matches;
}
?>
