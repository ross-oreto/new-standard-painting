import {$} from "./jquery.js";
import "./jquery.validate.js";

$(function () {
    $(".navbar-burger").on('click',function() {
        // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
        $(".navbar-burger").toggleClass("is-active");
        $(".navbar-menu").toggleClass("is-active");
    });

    $("#estimate-button").click(() => {
        const rules = {
            'name': {
                required: true
            }
            , 'address': {
                required: true
                , equalTo: "input[name='suggestedAddress']"
            }
            , 'email': {
                required: true,
                email: true
            }
            , 'phone': {
                phoneUS: true,
                required: {
                    depends: () => $("input[name=phone]").is(":checked")
                }
            }
        };
        const highlight = function(element, errorClass) {
            const el = $(element);
            el.fadeOut(function() {
                el.fadeIn();
                el.addClass("is-danger");
                el.siblings('label').addClass('has-text-danger');
            });
        }
        const unhighlight = function(element, errorClass, validClass) {
            $(element).removeClass("is-danger");
        }
        const options = {
            onkeyup: false
            , onfocusout: false
            , rules: rules
            , highlight: highlight
            , unhighlight: unhighlight
            , messages: {
                'address': { equalTo: 'Address not found' }
            }
        };

        const inputAddress = $("input[name='address']");
        const suggestedAddress = $("input[name='suggestedAddress']");
        if (validateForm(options)) {
            requestEstimate();
        } else if(inputAddress.val()) {
            $.get(`/address/${inputAddress.val()}`, (data) => {
                $("input[name='geocode']").val(data.geometry.coordinates.join(','));
                const p = data.properties;
                const address = `${p.name}, ${p.locality}, ${p.region_a}, ${p.postalcode}`;
                suggestedAddress.val(address);
                inputAddress.val(address);

                if(validateForm(options)) {
                    requestEstimate();
                }
            }).fail((data) => {
                suggestedAddress.val('');
                options.messages = { 'address': { equalTo: `${data.responseText}` }}
                validateForm(options);
            });
        }
    });

    function validateForm(options) {
        const form = $("#schedule");
        form.validate(options);
        return form.valid();
    }

    function requestEstimate() {

    }
});