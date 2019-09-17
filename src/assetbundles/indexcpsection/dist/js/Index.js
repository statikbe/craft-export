/**
 * Export plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    Statik
 * @copyright Copyright (c) 2019 Statik
 * @link      https://statik.be
 * @package   Export
 * @since     1.0.0
 */


// Toggle various field when changing element type
$(document).on('change', '#elementType', function () {

    $('.element-select').hide();

    var value = $(this).val().replace(/\\/g, '-');
    $('.element-select[data-type="' + value + '"]').show();

});

$('#elementType').trigger('change');

// Toggle the Entry Type field when changing the section select
$(document).on('change', '.element-parent-group select', function () {
    var sections = $(this).parents('.element-sub-group').data('items');
    var entryType = 'item_' + $(this).val();
    var entryTypes = sections[entryType];

    var currentValue = $('.element-child-group select').val();

    var newOptions = '<option value="">' + Craft.t('export', 'None') + '</option>';
    $.each(entryTypes, function (index, value) {
        if (index) {
            newOptions += '<option value="' + index + '">' + value + '</option>';
        }
    });

    $('.element-child-group select').html(newOptions);

    // Select the first non-empty, or pre-selected
    if (currentValue) {
        $('.element-child-group select').val(currentValue);
    } else {
        $($('.element-child-group select').children()[1]).attr('selected', true);
    }
});

$('.element-parent-group select').trigger('change');

$('#allCustomFields').on('change', function() {
    console.log($('#allCustomFields')[0].checked);
    var checkboxes = $('.fieldsCustomData');
    for(var i = 0, n = checkboxes.length; i < n; i++) {
        checkboxes[i].checked = $('#allCustomFields')[0].checked;
    }
});
