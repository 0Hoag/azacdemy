jQuery(document).ready(function ($) {
    // Single and multisheet connection tab toggle
   $('.cf7gs-tab-toggle').on('click', function () {
        var tabClass = $(this).data('tab');

        // Toggle tab content visibility
        $('.cf7-sub-tab').hide();
        $('.' + tabClass).show();

        // Toggle active tab styling
        if (tabClass === 'cf7-sub-tab-multi') {
            $('.cf7-sub-tab-single-li').removeClass('cf7-sub-tab-active');
            $('.cf7-sub-tab-multi-li').addClass('cf7-sub-tab-active');
        } else {
            $('.cf7-sub-tab-multi-li').removeClass('cf7-sub-tab-active');
            $('.cf7-sub-tab-single-li').addClass('cf7-sub-tab-active');
        }
    });

    // Optional: Ensure single tab is shown by default
    $('.cf7-sub-tab').hide();
    $('.cf7-sub-tab-single').show();

    // Verify API code
    $(document).on('click', '#save-gs-code', function () {
        $(".loading-sign").addClass("loading");
        var data = {
            action: 'verify_gs_integation',
            code: $('#gs-code').val(),
            security: $('#gs-ajax-nonce').val()
        };
        $.post(ajaxurl, data, function (response) {
            $(".loading-sign").removeClass("loading");
            $("#gs-validation-message").empty();
            if (!response.success) {
                $("<span class='error-message'>Access code Can't be blank</span>").appendTo('#gs-validation-message');
            } else {
                $("<span class='gs-valid-message'>Your Google Access Code is Authorized and Saved.</span>").appendTo('#gs-validation-message');
                setTimeout(function () {
                    window.location.href = $("#redirect_auth").val();
                }, 1000);
            }
        });
    });

    // Deactivate API code
    $(document).on('click', '#deactivate-log', function () {
        $(".loading-sign-deactive").addClass("loading");
        if (confirm("Are You sure you want to deactivate Google Integration ?")) {
            var data = {
                action: 'deactivate_gs_integation',
                security: $('#gs-ajax-nonce').val()
            };
            $.post(ajaxurl, data, function (response) {
                if (response == -1) return false;
                $(".loading-sign-deactive").removeClass("loading");
                $("#deactivate-message").empty();

                if (!response.success) {
                    alert('Error while deactivation');
                } else {
                    $("<span class='gs-valid-message'>Your account is removed. Reauthenticate again to integrate Contact Form with Google Sheet.</span>").appendTo('#deactivate-message');
                    setTimeout(() => location.reload(), 1000);
                }
            });
        } else {
            $(".loading-sign-deactive").removeClass("loading");
        }
    });

    // Clear debug logs
    $(document).on('click', '.debug-clear', function () {
        $(".clear-loading-sign").addClass("loading");
        $.post(ajaxurl, {
            action: 'gs_clear_log',
            security: $('#gs-ajax-nonce').val()
        }, function (response) {
            if (response.success) {
                $(".clear-loading-sign").removeClass("loading");
                $("#gs-validation-message").html(`<span class='gs-valid-message'>${response.data}</span>`);
                setTimeout(() => location.reload(), 1000);
            }
        });
    });

    // Clear system status logs
    $(document).on('click', '.clear-content-logs-cf7', function () {
        $(".clear-loading-sign-logs-cf7").addClass("loading");
        $.post(ajaxurl, {
            action: 'cf7_clear_debug_log',
            security: $('#gs-ajax-nonce').val()
        }, function (response) {
            if (response.success) {
                $(".clear-loading-sign-logs-cf7").removeClass("loading");
                $('.clear-content-logs-msg-cf7').html('Logs are cleared.');
                setTimeout(() => location.reload(), 1000);
            }
        });
    });

    // Toggle error log view
    $(document).on('click', '.closeView', function () {
        $('.closeView').text("View").removeClass('closeView');
        $('button').addClass('gsc-cf7free-logs');
        $('.system-error-cf7free-logs').hide();
    });

    $(document).on('click', '.gsc-cf7free-logs', function () {
        $('.gsc-cf7free-logs').text("Close").addClass('closeView');
        $('button').removeClass('gsc-cf7free-logs');
        $('.system-error-cf7free-logs').show();
    });

    $('.system-error-cf7free-logs').hide().on('click', function (e) {
        e.stopPropagation();
    });

    // Handle localStorage for Google Drive message
    if (localStorage.getItem('googleDriveMsgHidden') === 'true') {
        $('#google-drive-msg').hide();
    }
    $('.button_cf7formgsc').on('click', function () {
        $('#google-drive-msg').hide();
        localStorage.setItem('googleDriveMsgHidden', 'true');
    });
    $('#deactivate-log').on('click', function () {
        $('#google-drive-msg').show();
        localStorage.removeItem('googleDriveMsgHidden');
    });

    // FAQ behavior
    const faqTrigger = $('.cd-faq-trigger');
    faqTrigger.on('click', function (event) {
        event.preventDefault();
        const dataid = $(this).attr('data-id');
        for (let i = 1; i <= 5; i++) {
            if (i != dataid && i != 5) {
                $('.cd-faq-content' + i).hide(200);
            }
        }
        $(this).next('.cd-faq-content' + dataid).slideToggle(200).end().parent('li').toggleClass('content-visible');
    });

    // PRO feature popup logic
    const opener = document.getElementById('opener');
    const opener2 = document.getElementById('opener2');
    const popup = document.getElementById('popup-gs');
    const popup2 = document.getElementById('popup-gs2');
    const closeButton = document.getElementById('closeButton');
    const closeButton2 = document.getElementById('closeButton2');
    const popupOuter = document.getElementById('popup-outer-gs');
    const popupOuter2 = document.getElementById('popup-outer-gs2');

    if (opener) opener.addEventListener('click', () => fadeIn(popup));
    if (opener2) opener2.addEventListener('click', () => fadeIn(popup2));
    if (closeButton) closeButton.addEventListener('click', () => fadeOut(popup));
    if (closeButton2) closeButton2.addEventListener('click', () => fadeOut(popup2));
    if (popupOuter) popupOuter.addEventListener('click', e => { if (e.target === popupOuter) fadeOut(popup); });
    if (popupOuter2) popupOuter2.addEventListener('click', e => { if (e.target === popupOuter2) fadeOut(popup2); });

    function fadeIn(element) {
        if (!element) return;
        let opacity = 0;
        element.style.opacity = opacity;
        element.style.display = 'block';
        const fadeInInterval = setInterval(() => {
            if (opacity < 1) {
                opacity += 0.1;
                element.style.opacity = opacity;
            } else {
                clearInterval(fadeInInterval);
            }
        }, 50);
    }

    function fadeOut(element) {
        if (!element) return;
        let opacity = 1;
        const fadeOutInterval = setInterval(() => {
            if (opacity > 0) {
                opacity -= 0.1;
                element.style.opacity = opacity;
            } else {
                clearInterval(fadeOutInterval);
                element.style.display = 'none';
            }
        }, 50);
    }
});