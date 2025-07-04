/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */
const ps_legalcompliance = {
  init: () => {
    const info_panel = $('.info_panel');
    const cronjob_container = $('.cronjobs .job');
    const btn_change_license_code = $('#btn_change_license_code');

    info_panel.find('.panel-footer a').on('click', (e) => {
      e.preventDefault();

      const element = $(e.currentTarget);

      if (!info_panel.hasClass('closed')) {
        info_panel.addClass('closed');

        element.find('i').text('expand_more');

        localStorage.setItem('toggle_gc_header', 'closed');
      } else {
        info_panel.removeClass('closed');

        element.find('i').text('expand_less');

        localStorage.setItem('toggle_gc_header', 'opened');
      }
    });

    const toggle_gc_header = localStorage.getItem('toggle_gc_header');

    if (toggle_gc_header == 'closed') {
      info_panel.addClass('closed');

      info_panel.find('.panel-footer a i').text('expand_more');
    }

    cronjob_container.find('.job_open').on('click', (e) => {
      e.preventDefault();

      const element = $(e.currentTarget);

      element.hide().closest('.job').find('.job_body').show();

      $('.job_close').show();
    });

    cronjob_container.find('.job_close').on('click', (e) => {
      e.preventDefault();

      const element = $(e.currentTarget);

      element.hide().closest('.job_body').hide();

      $('.job_open').show();
    });

    btn_change_license_code.on('click', (e) => {
      if (window.confirm(txtUpdateLicenseCode)) {
        return true;
      } else {
        e.preventDefault();

        return false;
      }
    });

    $('input[name="payment[awaiting_payment]"]').on('change', (e) => {
      ps_legalcompliance.togglePaymentRow();
    });

    $('input[name="payment[show_payment_logo]"]').on('change', (e) => {
      ps_legalcompliance.toggleLogoRow();
    });

    ps_legalcompliance.togglePaymentRow();
    ps_legalcompliance.toggleLogoRow();
  },

  togglePaymentRow: () => {
    ps_legalcompliance.toggleRow('input[name="payment[awaiting_payment]"]', '.awaiting_payment_os_row');
  },

  toggleLogoRow: () => {
    ps_legalcompliance.toggleRow('input[name="payment[show_payment_logo]"]', '.payment_logo_row');
  },

  toggleRow: (radioSelector, rowSelector) => {
    if ($(radioSelector).length === 0) {
      return;
    }

    let value = $(radioSelector + ':checked').val();

    if (value === '1') {
      $(rowSelector).show();
    } else {
      $(rowSelector).hide();
    }
  },

  rearrangeTabs: () => {
    if (
      typeof tabClassnames === 'undefined'
      || tabClassnames.length === 0
    ) {
      return;
    }

    for (classname of tabClassnames) {
      let id = 'subtab-' + classname;
      let menuItem = $('#' + id).parent();

      if (menuItem.length === 0) {
        continue;
      }

      $('#head_tabs .nav').append(menuItem);
    };
  }
}

$(() => {
  ps_legalcompliance.init();
  ps_legalcompliance.rearrangeTabs();

  $('input[name="label[AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL]"]').on('change', (e) => {
    ps_legalcompliance.toggleRow('input[name="label[AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL]"]', '.label_delivery_additional');
  });

  ps_legalcompliance.toggleRow('input[name="label[AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL]"]', '.label_delivery_additional');;
});
