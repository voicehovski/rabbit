<?php die("Access Denied"); ?>#x#a:2:{s:6:"result";a:2:{s:6:"report";a:0:{}s:2:"js";s:1533:"
  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['День', 'Замовлення', 'Загальна кількість проданих товарів', 'Чистий прибуток'], ['2018-01-12', 0,0,0], ['2018-01-13', 0,0,0], ['2018-01-14', 0,0,0], ['2018-01-15', 0,0,0], ['2018-01-16', 0,0,0], ['2018-01-17', 0,0,0], ['2018-01-18', 0,0,0], ['2018-01-19', 0,0,0], ['2018-01-20', 0,0,0], ['2018-01-21', 0,0,0], ['2018-01-22', 0,0,0], ['2018-01-23', 0,0,0], ['2018-01-24', 0,0,0], ['2018-01-25', 0,0,0], ['2018-01-26', 0,0,0], ['2018-01-27', 0,0,0], ['2018-01-28', 0,0,0], ['2018-01-29', 0,0,0], ['2018-01-30', 0,0,0], ['2018-01-31', 0,0,0], ['2018-02-01', 0,0,0], ['2018-02-02', 0,0,0], ['2018-02-03', 0,0,0], ['2018-02-04', 0,0,0], ['2018-02-05', 0,0,0], ['2018-02-06', 0,0,0], ['2018-02-07', 0,0,0], ['2018-02-08', 0,0,0], ['2018-02-09', 0,0,0]  ]);
        var options = {
          title: 'Звіт за період з П'ятниця, 12 січня 2018 по Субота, 10 лютого 2018',
            series: {0: {targetAxisIndex:0},
                   1:{targetAxisIndex:0},
                   2:{targetAxisIndex:1},
                  },
                  colors: ["#00A1DF", "#A4CA37","#E66A0A"],
        };

        var chart = new google.visualization.LineChart(document.getElementById('vm_stats_chart'));

        chart.draw(data, options);
      }
";}s:6:"output";s:0:"";}