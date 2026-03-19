import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    PieController,
    ArcElement
  } from 'chart.js';
  import Icons from '@typo3/backend/icons.js';
  
  class StatisticsChart {
    constructor() {
      Chart.register(
        BarController,
        BarElement,
        CategoryScale,
        LinearScale,
        Tooltip,
        Legend,
        PieController,
        ArcElement
      );
      this.initialize();
    }
  
    initialize() {
      const statisticsFormSubmit = document.querySelector('#statisticsFormSubmit');
      const dateField = document.querySelector('[name="date"]');
      const dateScopeField = document.querySelector('[name="date_scope"]');
  
      if (statisticsFormSubmit && dateField && dateScopeField) {
        let initialDateValue = dateField.value;
        let initialDateScopeValue = dateScopeField.value;
  
        const checkFormChange = () => {
          statisticsFormSubmit.disabled = dateField.value === initialDateValue
            && dateScopeField.value === initialDateScopeValue;
        };
  
        dateField.addEventListener('input', checkFormChange);
        dateScopeField.addEventListener('input', checkFormChange);
  
        Icons.getIcon('spinner-circle', Icons.sizes.large, null, 'default', 'inline').then((loaderIcon) => {
          statisticsFormSubmit.addEventListener('click', () => {
            const loader = document.getElementById('ns-t3ai__loader') || document.getElementById('t3cs-statistics-loader');
            if (loader && loaderIcon) {
              loader.insertAdjacentHTML('beforeend', loaderIcon);
              loader.classList.add('ns-show-overlay');
            }
          });
        }).catch(() => {});
      }
  
      const chartSelectors = '.t3ai-chart, .ai-statistics-chart';
      const allCharts = document.querySelectorAll(chartSelectors);
      if (allCharts.length > 0) {
        allCharts.forEach((chart) => {
          if (!chart.hasAttribute('chart-data')) {
            return;
          }
          const chartData = chart.getAttribute('chart-data');
          if (this.isValidJSON(chartData)) {
            new Chart(chart.getContext('2d'), JSON.parse(chartData));
          }
        });
      }
    }
  
    isValidJSON(jsonString) {
      try {
        JSON.parse(jsonString);
        return true;
      } catch {
        return false;
      }
    }
  }
  
  export default new StatisticsChart();