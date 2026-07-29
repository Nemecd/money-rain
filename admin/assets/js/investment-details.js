/*
==================================================
        MONEY RAIN
Investment Details
==================================================
*/

document.addEventListener("DOMContentLoaded", () => {
  const approveBtn = document.getElementById("approveBtn");
  const rejectBtn = document.getElementById("rejectBtn");

  const confirmApprove = document.getElementById("confirmApprove");
  const confirmReject = document.getElementById("confirmReject");

  const approveModal = new bootstrap.Modal(
    document.getElementById("approveModal"),
  );

  const rejectModal = new bootstrap.Modal(
    document.getElementById("rejectModal"),
  );

  let investmentId = "";

  const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  /*
    ==============================================
                OPEN APPROVE MODAL
    ==============================================
    */

  if (approveBtn) {
    approveBtn.addEventListener("click", () => {
      investmentId = approveBtn.dataset.id;

      approveModal.show();
    });
  }

  /*
    ==============================================
                OPEN REJECT MODAL
    ==============================================
    */

  if (rejectBtn) {
    rejectBtn.addEventListener("click", () => {
      investmentId = rejectBtn.dataset.id;

      rejectModal.show();
    });
  }

  /*
    ==============================================
                APPROVE PAYMENT
    ==============================================
    */

  confirmApprove?.addEventListener("click", async () => {
    loading(confirmApprove, true);

    try {
      const response = await fetch(
        "approve-payment.php",

        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",

            "X-CSRF-Token": csrfToken,

            "X-Requested-With": "XMLHttpRequest",
          },

          body: JSON.stringify({
            investment_id: investmentId,
          }),
        },
      );

      const result = await response.json();
      if (result.csrf_token) {
        document.querySelector('meta[name="csrf-token"]').setAttribute(
          "content",

          result.csrf_token,
        );
      }

      if (!result.success) {
        throw new Error(result.message);
      }

      approveModal.hide();

      showNotification(
        "Investment approved successfully.",

        "success",
      );

      setTimeout(() => {
        location.reload();
      }, 1500);
    } catch (error) {
      showNotification(
        error.message,

        "error",
      );
    } finally {
      loading(confirmApprove, false);
    }
  });

  /*
    ==============================================
                REJECT PAYMENT
    ==============================================
    */

  confirmReject?.addEventListener("click", async () => {
    const reason = document.getElementById("rejectReason").value;

    const comment = document.getElementById("rejectComment").value.trim();

    if (reason === "") {
      showNotification(
        "Please select a rejection reason.",

        "warning",
      );

      return;
    }

    loading(confirmReject, true);

    try {
      const response = await fetch(
        "reject-payment.php",

        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",

            "X-CSRF-Token": csrfToken,

            "X-Requested-With": "XMLHttpRequest",
          },

          body: JSON.stringify({
            investment_id: investmentId,

            reason,

            comment,
          }),
        },
      );

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message);
      }

      rejectModal.hide();

      showNotification(
        "Investment rejected.",

        "success",
      );

      setTimeout(() => {
        location.reload();
      }, 1500);
    } catch (error) {
      showNotification(
        error.message,

        "error",
      );
    } finally {
      loading(confirmReject, false);
    }
  });
});

/*
==================================================
        BUTTON LOADING
==================================================
*/

function loading(button, state) {
  if (state) {
    button.disabled = true;

    button.dataset.original = button.innerHTML;

    button.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2"></span>Please wait...';
  } else {
    button.disabled = false;

    button.innerHTML = button.dataset.original;
  }
}
