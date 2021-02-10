/**
 * @file
 * Author: Synapse-studio.
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    siteReady();
  });

  function siteReady() {
    const END = Date.now() + 3000;
    var body = document.body;

    var interval = setInterval(() => {
      if (body.classList.contains('site-is-ready')) {
        clearInterval(interval);
        return;
      }
      if (Date.now() > END) {
        body.classList.add('site-is-ready');
        clearInterval(interval);
      }
    }, 1000);

    window.onload = () => {
      body.classList.add('site-is-ready');
    }
  }

  if (typeof (Drupal) === 'object') {
    Drupal.behaviors.adaptive = {
      attach: function (context) {}
    };
  }

})(this.jQuery);