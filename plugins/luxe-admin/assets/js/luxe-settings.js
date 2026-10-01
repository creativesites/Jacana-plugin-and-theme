jQuery(document).ready(function($) {
    // Tab Switching
    $('.luxe-tabs a').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('tab');
        
        $('.luxe-tabs a').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#tab-' + target).addClass('active');
        
        window.location.hash = target;
    });

    // Handle Hash on Load
    if (window.location.hash) {
        $(`.luxe-tabs a[data-tab="${window.location.hash.substring(1)}"]`).trigger('click');
    }

    // Radio Option Selection Visuals
    $('.luxe-skin-selector input').on('change', function() {
        $('.skin-option').removeClass('selected');
        $(this).closest('.skin-option').addClass('selected');
    });

    // Save Settings
    $('#luxe-save-settings').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.text('Saving...').prop('disabled', true);

        // Gather all inputs
        const settings = {
            theme: $('input[name="theme"]:checked').val() || 'default',
            login: {
                enabled: $('input[name="login_enabled"]').is(':checked') ? 1 : 0,
                logo: $('input[name="login_logo"]').val(),
                logo_width: $('input[name="login_logo_width"]').val(),
                msg: $('input[name="login_msg"]').val(),
                url: $('input[name="login_url"]').val(),
                title: $('input[name="login_title"]').val()
            },
            menu: {
                hide_wp_logo: $('input[name="hide_wp_logo"]').is(':checked') ? 1 : 0,
                hide_help_tabs: $('input[name="hide_help_tabs"]').is(':checked') ? 1 : 0,
                hide_screen_options: $('input[name="hide_screen_options"]').is(':checked') ? 1 : 0,
                hidden: $('input[name="hide_menu[]"]:checked').map(function() { return $(this).val(); }).get()
            },
            dashboard: {
                hide_default: $('input[name="hide_default_widgets"]').is(':checked') ? 1 : 0,
                hide_welcome_panel: $('input[name="hide_welcome_panel"]').is(':checked') ? 1 : 0,
                welcome_msg: $('textarea[name="welcome_msg"]').val()
            },
            appearance: {
                custom_palette: $('input[name="custom_palette"]').is(':checked') ? 1 : 0,
                bg: $('input[name="appearance_bg"]').val(),
                sidebar: $('input[name="appearance_sidebar"]').val(),
                accent: $('input[name="appearance_accent"]').val(),
                text: $('input[name="appearance_text"]').val(),
                card_bg: $('input[name="appearance_card_bg"]').val(),
                font: $('select[name="appearance_font"]').val(),
                radius: $('input[name="appearance_radius"]').val(),
                compact_mode: $('input[name="appearance_compact_mode"]').is(':checked') ? 1 : 0
            },
            white_label: {
                brand_name: $('input[name="brand_name"]').val(),
                brand_url: $('input[name="brand_url"]').val(),
                footer_text: $('input[name="footer_text"]').val(),
                disable_admin_notices: $('input[name="disable_admin_notices"]').is(':checked') ? 1 : 0,
                disable_update_nag: $('input[name="disable_update_nag"]').is(':checked') ? 1 : 0,
                hide_wp_version: $('input[name="hide_wp_version"]').is(':checked') ? 1 : 0
            }
        };

        $.ajax({
            url: luxeAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'luxe_save_settings',
                nonce: luxeAdmin.nonce,
                settings: JSON.stringify(settings)
            },
            success: function(response) {
                if (response.success) {
                    $btn.text('Saved!').css('background', '#10b981');
                    setTimeout(() => {
                        $btn.text(originalText).prop('disabled', false).css('background', '');
                    }, 2000);
                } else {
                    alert('Error saving settings: ' + response.data);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('Connection error.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
});
