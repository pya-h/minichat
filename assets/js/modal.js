function showModal(title, message, type = "info") {
    const modalOverlay = document.getElementById("modalOverlay");
    if (!modalOverlay) return;
    const modalContainer = document.getElementById("modalContainer");
    const modalTitle = document.getElementById("modalTitle");
    const modalMessage = document.getElementById("modalMessage");
    const modalIcon = document.getElementById("modalIcon");

    modalTitle.textContent = title;
    modalMessage.textContent = message;

    const iconContainer = modalIcon.parentElement;
    iconContainer.className = "modal-icon " + type;
    modalIcon.className = `fas ${getIconClass(type)}`;

    modalOverlay.style.display = "flex";
    setTimeout(() => {
        modalOverlay.classList.add("visible");
        modalContainer.classList.add("visible");
    }, 10);

    modalOverlay.focus();
}

function closeModal() {
    const modalOverlay = document.getElementById("modalOverlay");
    if (!modalOverlay) return;
    const modalContainer = document.getElementById("modalContainer");

    modalOverlay.classList.remove("visible");
    modalContainer.classList.remove("visible");

    setTimeout(() => {
        if (!modalOverlay.classList.contains("visible")) {
            modalOverlay.style.display = "none";
        }
    }, 400);
}

function getIconClass(type) {
    switch (type) {
        case "error":
            return "fa-exclamation-triangle";
        case "warning":
            return "fa-exclamation-circle";
        case "success":
            return "fa-check-circle";
        case "info":
        default:
            return "fa-info-circle";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const modalOverlay = document.getElementById("modalOverlay");
    if (modalOverlay) {
        modalOverlay.addEventListener("click", function (e) {
            if (
                e.target === this ||
                e.target.classList.contains("modal-close") ||
                e.target.parentElement.classList.contains("modal-close") ||
                e.target.classList.contains("btn")
            ) {
                closeModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            const modalOverlay = document.getElementById("modalOverlay");
            if (
                modalOverlay &&
                modalOverlay.style.display !== "none" &&
                modalOverlay.classList.contains("visible")
            ) {
                closeModal();
            }
        }
    });
});

window.alert = function (message) {
    showModal("Information", message, "info");
};
