document.addEventListener("DOMContentLoaded", () => {
  const violationDetails = document.getElementById("violationDetails");
  const violationIdInput = document.getElementById("violation_id");
  const responseMessage = document.getElementById("responseMessage");
  const appealForm = document.getElementById("AppealForm");
  const statusBar = document.querySelector(".status-bar");

  function readSelectedViolation() {
    const raw = localStorage.getItem("selected_violation");
    if (!raw) return null;

    try {
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  }

  function renderViolationDetails(violation) {
    if (!violationDetails) return;

    if (!violation) {
      if (statusBar) {
        statusBar.remove();
      }

      violationDetails.innerHTML = `
        <p>لم يتم العثور على تفاصيل المخالفة.</p>
        <p>الرجاء العودة إلى بوابة المواطن والضغط على "تقديم اعتراض" من بطاقة المخالفة المطلوبة.</p>
      `;
      return;
    }

    const type = violation.violation_type?.name ?? "-";
    const fine = violation.violation_type?.fine_amount ?? "-";
    const plate = violation.vehicle_snapshot?.plate_number ?? violation.vehicle?.plate_number ?? "-";
    const city = violation.violation_location?.city_record?.name ?? violation.violation_location?.city ?? "-";
    const street = violation.violation_location?.street_name ?? violation.location?.street_name ?? "-";
    const description = violation.description ?? "-";
    const date = violation.occurred_at ?? "-";

    violationDetails.innerHTML = `
      <p><strong>نوع المخالفة:</strong> ${type}</p>
      <p><strong>رقم اللوحة:</strong> ${plate}</p>
      <p><strong>قيمة الغرامة:</strong> ${fine}</p>
      <p><strong>المدينة:</strong> ${city}</p>
      <p><strong>الشارع:</strong> ${street}</p>
      <p><strong>الوصف:</strong> ${description}</p>
      <p><strong>التاريخ:</strong> ${date}</p>
    `;

    if (statusBar) {
      statusBar.innerHTML = `
        <span class="status-dot"></span>
        تم تحميل تفاصيل المخالفة
      `;
    }
  }

  const selectedViolation = readSelectedViolation();
  const storedViolationId = localStorage.getItem("violation_id");

  if (violationIdInput && storedViolationId) {
    violationIdInput.value = storedViolationId;
  }

  if (violationDetails) {
    renderViolationDetails(selectedViolation);
  }

  if (!appealForm) {
    return;
  }

  appealForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const violationId = localStorage.getItem("violation_id");
    const reason = document.getElementById("reason")?.value?.trim();

    if (!violationId || !reason) {
      if (responseMessage) {
        responseMessage.innerHTML = `<p style="color: red;">الرجاء إدخال سبب الاعتراض والتأكد من اختيار المخالفة.</p>`;
      }
      return;
    }

    const formData = new FormData();
    formData.append("violation_id", parseInt(violationId, 10));
    formData.append("reason", reason);

    try {
      const response = await fetch("/citizen/appeals", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
        },
        body: formData,
      });

      const contentType = response.headers.get("content-type") || "";
      const text = await response.text();

      if (!response.ok) {
        let message = text;

        if (contentType.includes("application/json")) {
          try {
            const parsed = JSON.parse(text);
            message = parsed.message || JSON.stringify(parsed);
          } catch (_) {
            message = text;
          }
        }

        if (responseMessage) {
          responseMessage.innerHTML = `<p style="color: red;">${message}</p>`;
        }
        return;
      }

      if (!contentType.includes("application/json")) {
        if (responseMessage) {
          responseMessage.innerHTML = `<p style="color: red;">استجابة غير متوقعة من الخادم.</p>`;
        }
        return;
      }

      const data = JSON.parse(text);

      if (!data.success) {
        const message = data.message || "تعذر إرسال الاعتراض.";
        if (responseMessage) {
          responseMessage.innerHTML = `<p style="color: red;">${message}</p>`;
        }
        return;
      }

      if (responseMessage) {
        responseMessage.innerHTML = `<p style="color: green;">تم إرسال الاعتراض بنجاح. ستتم إعادتك إلى بوابة المواطن خلال لحظات.</p>`;
      }

      localStorage.removeItem("selected_violation");

      window.setTimeout(() => {
        window.location.href = "/";
      }, 1800);
    } catch (error) {
      console.error("Submit Appeal error:", error);
      if (responseMessage) {
        responseMessage.innerHTML = `<p style="color: red;">حدث خطأ أثناء إرسال الاعتراض.</p>`;
      }
    }
  });
});
