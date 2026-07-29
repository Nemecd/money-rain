document.addEventListener("DOMContentLoaded", () => {
  const container = document.createElement("div");

  container.id = "notificationContainer";

  document.body.appendChild(container);
});

function showNotification(
  message,

  type = "error",

  duration = 4000,
) {
  let container = document.getElementById("notificationContainer");

  if (!container) {
    container = document.createElement("div");

    container.id = "notificationContainer";

    document.body.appendChild(container);
  }

  const notification = document.createElement("div");

  notification.className = `money-notification ${type}`;

  const iconMap = {
    success: "bi-check-circle-fill",

    error: "bi-exclamation-circle-fill",

    warning: "bi-exclamation-triangle-fill",

    info: "bi-info-circle-fill",
  };

  notification.innerHTML = `

        <div class="notification-icon">

            <i class="bi ${iconMap[type] || iconMap.error}"></i>

        </div>


        <div class="notification-message">

            ${message}

        </div>


        <button
            type="button"
            class="notification-close"
        >

            <i class="bi bi-x"></i>

        </button>

    `;

  container.appendChild(notification);

  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  const closeButton = notification.querySelector(".notification-close");

  const removeNotification = () => {
    notification.classList.remove("show");

    setTimeout(() => {
      notification.remove();
    }, 350);
  };

  closeButton.addEventListener(
    "click",

    removeNotification,
  );

  setTimeout(
    removeNotification,

    duration,
  );
}
function showConfirmModal(
  title,

  message,

  onConfirm,
) {
  const modal = document.getElementById("confirmModal");

  const titleElement = document.getElementById("confirmModalTitle");

  const messageElement = document.getElementById("confirmModalMessage");

  const yesButton = document.getElementById("confirmYesBtn");

  const noButton = document.getElementById("confirmCancelBtn");

  titleElement.textContent = title;

  messageElement.textContent = message;

  modal.classList.add("show");

  const closeModal = () => {
    modal.classList.remove("show");
  };

  yesButton.onclick = async () => {
    closeModal();

    await onConfirm();
  };

  noButton.onclick = closeModal;
}
