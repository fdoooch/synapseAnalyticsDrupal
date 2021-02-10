/**
 * @file
 * Author: Synapse-studio.
 */

(function ($) {
  'use strict';

  const DRAGTHRESHOLD = window.innerWidth > 1024 ? 20 : 0;

  $(document).ready(function () {
    moduleSlider();
  });

  function moduleSlider() {
    var $slider = $('.flickity');
    if ($slider.length) {
      $slider.flickity({
        dragThreshold: DRAGTHRESHOLD,
        cellAlign: 'left',
        autoPlay: 4000,
        pageDots: false,
        wrapAround: true,
        imagesLoaded: true,

      });
      $slider.on('dragStart.flickity', function (event, pointer) {
        $(this).addClass('is-dragging');
      });
      $slider.on('dragEnd.flickity', function (event, pointer) {
        $(this).removeClass('is-dragging');
      });
    }
  }
})(this.jQuery);
