jQuery(document).ready(function ($) {
    var results = [];
    var searchTimeout = null;
    var searchField = $('#billing_address_1');
    var cityField = $('#billing_city');
    var postalCodeField = $('#billing_postcode');
    var countryField = $('#billing_country');
    var notice_text = multiparcels.text.address_autocomplete_on;
    var notice_element = null;

    searchField.after($('<div id="multiparcels-autocomplete" style="display: none;"><div id="multiparcels-autocomplete-items"></div></div>'));
    searchField.before($('<div id="multiparcels-autocomplete-close" style="position: absolute;right: 40px;z-index: 10000;margin-top: 10px;display: none;"><span></span></div>'));

    if (notice_text !== null) {
        searchField.after($('<div id="multiparcels-autocomplete-notice">' + multiparcels.text.address_autocomplete_on + '</div>'));
        notice_element = $("#multiparcels-autocomplete-notice");
    }

    var autoComplete = $('#multiparcels-autocomplete');
    var autoCompleteItems = $('#multiparcels-autocomplete-items');
    var autoCompleteClose = $('#multiparcels-autocomplete-close');

    autoCompleteClose.on('click', function () {
        autoComplete.hide();
        autoCompleteClose.hide();

        return false;
    });

    $(document).mouseup(function (e) {
        var container = $("#billing_address_1_field");

        // if the target of the click isn't the container nor a descendant of the container
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            autoComplete.hide();
            autoCompleteClose.hide();
        }
    });

    autoCompleteItems.on('click', '.multiparcels-autocomplete-item', function () {
        searchField.trigger('multiparcels:select', $(this).attr('data-key'));
    });

    autoCompleteItems.on('multiparcels:searching', function () {
        var resultsHtml = multiparcels.text.searching;

        autoCompleteItems.html(resultsHtml);
        autoComplete.show();
        autoCompleteClose.show();
    });

    $(document).keyup(function (e) {
        if (e.keyCode === 27) {
            autoComplete.hide();
            autoCompleteClose.hide();
        }
    });

    searchField.on('multiparcels:select', function (e, resultKey) {
        var address = results[resultKey];

        searchField.val(address['address']);
        cityField.val(address['city']);
        postalCodeField.val(address['postal_code']);

        $('body').trigger('update_checkout');

        autoComplete.hide();
        autoCompleteClose.hide();
    });

    searchField.on('multiparcels:display_results', function (e) {
        var resultsHtml = '';

        if (results.length > 0) {
            $.each(results, function (key, result) {
                resultsHtml += "<div class='multiparcels-autocomplete-item' data-key='" + key + "'>" + result['preview'] + "</div>";
            });

            autoCompleteItems.html(resultsHtml);
            autoComplete.show();
            autoCompleteClose.show();
        } else {
            autoComplete.hide();
            autoCompleteClose.hide();
        }
    });

    searchField.on('keyup', function (e) {
        clearTimeout(searchTimeout);

        if (notice_element !== null) {
            notice_element.hide();
        }

        searchTimeout = setTimeout(function () {
            autoCompleteItems.trigger('multiparcels:searching');

            $.ajax({
                type: 'POST',
                url: multiparcels.ajax_url,
                data: {
                    'action': 'multiparcels_checkout_address_autocomplete',
                    'query': searchField.val(),
                    'country': countryField.val()
                },
                dataType: 'json',
                success: function (response) {
                    results = response.results;

                    searchField.trigger('multiparcels:display_results');
                }
            });
        }, 500);
    });
});

jQuery(document).on('updated_checkout', function (e, data) {
    jQuery(function ($) {
        $("#billing_address_1_field").removeClass('address-field'); // prevent auto update
        $('#multiparcels-autocomplete').hide();
    });
});
