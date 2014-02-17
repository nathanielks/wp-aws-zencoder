(function($) {

  $(document).ready(function() {

    $('.waz-settings').each(function() {
      var $container = $(this);

      $('.reveal-form a', $container).click(function() {
        var $form = $('form', $container);
        if ('block' == $form.css('display')) {
          $form.hide();
        }
        else {
          $form.show();
        }
        return false;
      });
    });

    $('.button.remove-keys').click(function() {
      $('input[name=api_key]').val('');
    });

  });

})(jQuery);
