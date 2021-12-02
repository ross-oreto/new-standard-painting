import {$} from "./jquery.js";

$(function () {
    if (window.lang.startsWith('es')) {
        $.extend( $.validator.messages, {
            required: "Este campo es obligatorio.",
            remote: "Por favor, rellena este campo.",
            email: "Por favor, escribe una dirección de correo válida.",
            url: "Por favor, escribe una URL válida.",
            date: "Por favor, escribe una fecha válida.",
            dateISO: "Por favor, escribe una fecha (ISO) válida.",
            number: "Por favor, escribe un número válido.",
            digits: "Por favor, escribe sólo dígitos.",
            creditcard: "Por favor, escribe un número de tarjeta válido.",
            equalTo: "Por favor, escribe el mismo valor de nuevo.",
            extension: "Por favor, escribe un valor con una extensión aceptada.",
            maxlength: $.validator.format( "Por favor, no escribas más de {0} caracteres." ),
            minlength: $.validator.format( "Por favor, no escribas menos de {0} caracteres." ),
            rangelength: $.validator.format( "Por favor, escribe un valor entre {0} y {1} caracteres." ),
            range: $.validator.format( "Por favor, escribe un valor entre {0} y {1}." ),
            max: $.validator.format( "Por favor, escribe un valor menor o igual a {0}." ),
            min: $.validator.format( "Por favor, escribe un valor mayor o igual a {0}." ),
            nifES: "Por favor, escribe un NIF válido.",
            nieES: "Por favor, escribe un NIE válido.",
            cifES: "Por favor, escribe un CIF válido."
        } );
    }

    $(".navbar-burger").on('click',function() {
        // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
        $(".navbar-burger").toggleClass("is-active");
        $(".navbar-menu").toggleClass("is-active");
    });

    $( "a.navbar-item.schedule" ).click(function(event) {
        event.preventDefault();
        $("html, body").animate({
            scrollTop: $($(this).attr("href")).offset().top - 40
        }, 500);
    });

    let validator = null;

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
            , 'confirm-email': {
                required: true,
                email: true,
                equalTo: 'input[name=email]'
            }
            , 'phone': {
                phoneUS: true,
                required: {
                    depends: () => $("input[name=contact]").is(":checked")
                }
            }
            , 'captcha': {
                required: true
                , remote: {
                    url: '/validate-captcha'
                    , error: (data) => {
                        const el = $('#captcha');
                        el.attr('src', el.attr('src') + '?' + Math.random());
                        $('input[name="captcha"]').val('');
                        validator.showErrors({
                            'captcha': data.responseText
                        });
                    }
                }
            }
        };
        const highlight = function(element, errorClass) {
            const el = $(element);
            el.addClass("is-danger");
            setTimeout(() => {
                el.siblings('label').addClass('has-text-danger');
            }, 100);
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
                'address': { equalTo: '' }
                , 'captcha': { remote: '' }
            }
            , lang: 'en'
        };

        const inputAddress = $("input[name='address']");
        const suggestedAddress = $("input[name='suggestedAddress']");
        if (validateForm(options)) {
            requestEstimate();
        } else if(inputAddress.val()) {
            $.get(`/address/${inputAddress.val()}`, (data) => {
                $("input[name='geocode']").val(`${data.position.lat},${data.position.lng}`);
                const address = `${data.address.label}`;
                suggestedAddress.val(address);
                inputAddress.val(address);

                if(validateForm(options)) {
                    requestEstimate();
                }
            }).fail((data) => {
                suggestedAddress.val('');
                validator.showErrors({ 'address': data.responseText });
            });
        }
    });

    function validateForm(options) {
        const form = $("#schedule");
        validator = form.validate(options);
        return form.valid();
    }

    function requestEstimate() {
        const loader = $("#loadingDiv");
        loader.addClass("is-active");
        $.post(`/`, $('form#schedule').serialize(), (data) => {
            loader.removeClass("is-active");
            $('#schedule-box').html(data);
        }).fail((data) => {
            loader.removeClass("is-active");
            validator.showErrors(JSON.parse(data.responseText));
        });
    }
});