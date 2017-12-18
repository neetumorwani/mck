(function ($) {
    Drupal.behaviors.csvImporter_charts = {
        attach:function () {
            if (Drupal.settings.csvImporter.charts) {
                for (var chart in Drupal.settings.csvImporter.charts) {
                    new Highcharts.Chart(Drupal.settings.csvImporter.charts[chart]);
                }
            }
        }
    };
})(jQuery);