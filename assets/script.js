// Blood Donation Management System — script.js
// Basic client-side validation used by the forms across the site.

document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll("form[data-validate]");

    forms.forEach(function (form) {
        form.addEventListener("submit", function (e) {
            let valid = true;
            let message = "";

            const required = form.querySelectorAll("[required]");
            required.forEach(function (field) {
                if (!field.value || field.value.trim() === "") {
                    valid = false;
                    message = "Please fill in all required fields.";
                    field.style.borderColor = "#b30000";
                } else {
                    field.style.borderColor = "#ccc";
                }
            });

            const contact = form.querySelector("[name='contact_info']");
            if (contact && contact.value && !/^[0-9+\-\s]{7,15}$/.test(contact.value)) {
                valid = false;
                message = "Please enter a valid contact number.";
                contact.style.borderColor = "#b30000";
            }

            const age = form.querySelector("[name='age']");
            if (age && age.value && (age.value < 18 || age.value > 65)) {
                valid = false;
                message = "Donor age must be between 18 and 65.";
                age.style.borderColor = "#b30000";
            }

            if (!valid) {
                e.preventDefault();
                alert(message);
            }
        });
    });
});
