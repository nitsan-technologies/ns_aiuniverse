require([
    "TYPO3/CMS/Backend/Icons",
    "TYPO3/CMS/Dashboard/Contrib/chartjs"
  ], (Icons, Chart) => {
    const statisticsFormSubmit = document.querySelector("#statisticsFormSubmit");
    const dateField = document.querySelector('[name="date"]');
    const dateScopeField = document.querySelector('[name="date_scope"]');

    if (statisticsFormSubmit && dateField && dateScopeField) {
      const initialDateValue = dateField.value;
      const initialDateScopeValue = dateScopeField.value;

      function checkFormChange() {
        statisticsFormSubmit.disabled = dateField.value === initialDateValue &&
          dateScopeField.value === initialDateScopeValue;
      }

      dateField.addEventListener("input", checkFormChange);
      dateScopeField.addEventListener("input", checkFormChange);

      Icons.getIcon("spinner-circle", Icons.sizes.large).then(function (loaderIcon) {
        statisticsFormSubmit.addEventListener("click", () => {
          const loader = document.getElementById("ns-t3ai__loader") || document.getElementById("t3cs-statistics-loader");
          if (loader && loaderIcon) {
            loader.insertAdjacentHTML("beforeend", loaderIcon);
            loader.classList.add("ns-show-overlay");
          }
        });
      }).catch(function () {});
    }

    const chartSelectors = ".t3ai-chart, .ai-statistics-chart";
    const allCharts = document.querySelectorAll(chartSelectors);
    if (allCharts.length > 0) {
      allCharts.forEach((chart) => {
        if (!chart.hasAttribute("chart-data")) {
          return;
        }
        const chartData = chart.getAttribute("chart-data");
        if (isValidJSON(chartData)) {
          new Chart(chart.getContext("2d"), JSON.parse(chartData));
        }
      });
    }
  
    function isValidJSON(jsonString) {
      try {
        JSON.parse(jsonString);
        return true;
      } catch (e) {
        return false;
      }
    }
  });