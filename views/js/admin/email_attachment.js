/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

$(document).ready(function(){
    var email_attacher = new EmailAttach();
    email_attacher.init();
});

var EmailAttach;
EmailAttach = function () {
    this.left_column_checkbox_id = 'input[id^=mail_]';
    this.email_attach_form_id = '#emailAttachementsManager';
    this.right_column_checked_checkboxes = 'input[id^=attach_]:checked';
    this.select_all_left_column_id = '#selectall_attach';
    this.select_all_right_column_id = 'input[id^=selectall_opt_]';

    this.init = function () {
        var that = this;

        $(this.left_column_checkbox_id).on('click', function () {
            var id_clicked = $(this).prop('id');
            id_clicked = that.cleanLeftColumnId(id_clicked);
            var have_to_check_checkbox = $(this).prop('checked') ? true : false;
            that.selectAllFollowingOptions(id_clicked, have_to_check_checkbox);
        });

        $(this.select_all_left_column_id).on('click', function () {
            var checked_status = $(this).prop('checked') ? true : false;
            that.selectEverything(checked_status);
        });

        $(this.select_all_right_column_id).on('click', function () {
            var checked_status = $(this).prop('checked') ? true : false;
            var id_clicked = $(this).prop('id');
            id_clicked = that.cleanTopRowId(id_clicked);
            that.selectEverythingRight(id_clicked, checked_status);
        });
    }

    this.cleanLeftColumnId = function (full_id) {
        var splitted_id = full_id.split('_');
        return splitted_id[1];
    }

    this.cleanTopRowId = function (full_id) {
        var splitted_id = full_id.split('_');
        return splitted_id[2];
    }

    this.selectAllFollowingOptions = function (base_id, checked_status) {
        $('input[id^=attach_' + base_id + '_]').each(function () {
            $(this).prop('checked', checked_status);
        });
    }

    this.selectEverything = function (checked_status) {
        $('input[id^=mail_]').each(function () {
            $(this).prop('checked', checked_status);
        });

        $('input[id^=attach_]').each(function () {
            $(this).prop('checked', checked_status);
        });

        $('input[id^=selectall_opt_]').each(function () {
            $(this).prop('checked', checked_status);
        });
    }

    this.selectEverythingRight = function (base_id, checked_status) {
        $('input[id$=_'+base_id+']').each(function () {
            $(this).prop('checked', checked_status);
        });
    }
};
