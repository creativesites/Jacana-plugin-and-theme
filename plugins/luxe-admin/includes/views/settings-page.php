<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = \Luxe_Admin\Core\Main::get_settings();
?>

<div class="wrap luxe-admin-settings-wrap">
	<header class="luxe-header">
		<div class="luxe-branding">
			<h1>Luxe Admin</h1>
			<p class="subtitle">Premium White-Label Dashboard</p>
		</div>
		<div class="luxe-actions">
			<button id="luxe-save-settings" class="button button-primary button-hero">Save Changes</button>
		</div>
	</header>

	<div class="luxe-main-grid">
		<aside class="luxe-sidebar">
			<nav class="luxe-tabs">
				<a href="#general" class="active" data-tab="general"><span class="dashicons dashicons-admin-generic"></span> General Skins</a>
				<a href="#login" data-tab="login"><span class="dashicons dashicons-lock"></span> Login Page</a>
				<a href="#menu" data-tab="menu"><span class="dashicons dashicons-menu"></span> Menu Control</a>
				<a href="#dashboard" data-tab="dashboard"><span class="dashicons dashicons-dashboard"></span> Dashboard</a>
				<a href="#whitelabel" data-tab="whitelabel"><span class="dashicons dashicons-admin-site"></span> White Label</a>
			</nav>
		</aside>

		<main class="luxe-content">
			<!-- General Section -->
			<section id="tab-general" class="tab-content active">
				<h3>Admin Appearance</h3>
				<p>Select a modern skin for your WordPress dashboard.</p>
				
				<div class="luxe-skin-selector">
					<label class="skin-option <?php echo ( 'default' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="default" <?php checked( $settings['theme'], 'default' ); ?>>
						<div class="skin-preview default"></div>
						<span>Default WP</span>
					</label>
					<label class="skin-option <?php echo ( 'midnight' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="midnight" <?php checked( $settings['theme'], 'midnight' ); ?>>
						<div class="skin-preview midnight"></div>
						<span>Midnight Dark</span>
					</label>
					<label class="skin-option <?php echo ( 'glass' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="glass" <?php checked( $settings['theme'], 'glass' ); ?>>
						<div class="skin-preview glass"></div>
						<span>Glassmorphism</span>
					</label>
					<label class="skin-option <?php echo ( 'sandstone' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="sandstone" <?php checked( $settings['theme'], 'sandstone' ); ?>>
						<div class="skin-preview sandstone"></div>
						<span>Sandstone</span>
					</label>
					<label class="skin-option <?php echo ( 'emerald' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="emerald" <?php checked( $settings['theme'], 'emerald' ); ?>>
						<div class="skin-preview emerald"></div>
						<span>Emerald</span>
					</label>
					<label class="skin-option <?php echo ( 'sunset' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="sunset" <?php checked( $settings['theme'], 'sunset' ); ?>>
						<div class="skin-preview sunset"></div>
						<span>Sunset</span>
					</label>
					<label class="skin-option <?php echo ( 'graphite' === $settings['theme'] ) ? 'selected' : ''; ?>">
						<input type="radio" name="theme" value="graphite" <?php checked( $settings['theme'], 'graphite' ); ?>>
						<div class="skin-preview graphite"></div>
						<span>Graphite</span>
					</label>
				</div>

				<hr style="border:0; border-top: 1px solid #eee; margin:30px 0;">

				<h3>Look & Feel Controls</h3>
				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="custom_palette" value="1" <?php checked( $settings['appearance']['custom_palette'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Enable Custom Color Palette</span>
				</div>

				<div class="luxe-field-grid">
					<div class="luxe-field-group stacked">
						<label>Background</label>
						<input type="color" name="appearance_bg" value="<?php echo esc_attr( $settings['appearance']['bg'] ? $settings['appearance']['bg'] : '#f8fafc' ); ?>">
					</div>
					<div class="luxe-field-group stacked">
						<label>Sidebar</label>
						<input type="color" name="appearance_sidebar" value="<?php echo esc_attr( $settings['appearance']['sidebar'] ? $settings['appearance']['sidebar'] : '#1e293b' ); ?>">
					</div>
					<div class="luxe-field-group stacked">
						<label>Accent</label>
						<input type="color" name="appearance_accent" value="<?php echo esc_attr( $settings['appearance']['accent'] ? $settings['appearance']['accent'] : '#0ea5e9' ); ?>">
					</div>
					<div class="luxe-field-group stacked">
						<label>Text</label>
						<input type="color" name="appearance_text" value="<?php echo esc_attr( $settings['appearance']['text'] ? $settings['appearance']['text'] : '#e2e8f0' ); ?>">
					</div>
					<div class="luxe-field-group stacked">
						<label>Card Background</label>
						<input type="color" name="appearance_card_bg" value="<?php echo esc_attr( $settings['appearance']['card_bg'] ? $settings['appearance']['card_bg'] : '#ffffff' ); ?>">
					</div>
				</div>

				<div class="luxe-field-grid">
					<div class="luxe-field-group stacked">
						<label>Typography Style</label>
						<select name="appearance_font">
							<option value="system" <?php selected( $settings['appearance']['font'], 'system' ); ?>>System UI</option>
							<option value="modern" <?php selected( $settings['appearance']['font'], 'modern' ); ?>>Modern Sans</option>
							<option value="serif" <?php selected( $settings['appearance']['font'], 'serif' ); ?>>Elegant Serif</option>
						</select>
					</div>
					<div class="luxe-field-group stacked">
						<label>Corner Radius (px)</label>
						<input type="number" min="0" max="24" name="appearance_radius" value="<?php echo esc_attr( $settings['appearance']['radius'] ); ?>">
					</div>
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="appearance_compact_mode" value="1" <?php checked( $settings['appearance']['compact_mode'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Enable Compact Density Mode</span>
				</div>
			</section>

			<!-- Login Section -->
			<section id="tab-login" class="tab-content">
				<h3>Login Customizer</h3>
				<p>Make the login page feel like your brand.</p>
				
				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="login_enabled" value="1" <?php checked( $settings['login']['enabled'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Enable Custom Login Styles</span>
				</div>

				<div class="luxe-field-group stacked">
					<label>Login Logo URL</label>
					<input type="text" name="login_logo" value="<?php echo esc_attr( $settings['login']['logo'] ); ?>" placeholder="https://example.com/logo.png">
				</div>

				<div class="luxe-field-group stacked">
					<label>Logo Width (px)</label>
					<input type="number" min="80" max="500" name="login_logo_width" value="<?php echo esc_attr( $settings['login']['logo_width'] ); ?>" placeholder="200">
				</div>

				<div class="luxe-field-group stacked">
					<label>Logo Link URL</label>
					<input type="text" name="login_url" value="<?php echo esc_attr( $settings['login']['url'] ); ?>" placeholder="https://example.com">
				</div>

				<div class="luxe-field-group stacked">
					<label>Logo Title Text</label>
					<input type="text" name="login_title" value="<?php echo esc_attr( $settings['login']['title'] ); ?>" placeholder="Your Company Name">
				</div>

				<div class="luxe-field-group">
					<label>Custom Login Message</label>
					<input type="text" name="login_msg" value="<?php echo esc_attr( $settings['login']['msg'] ); ?>" placeholder="e.g. Agency Client Portal">
				</div>
			</section>

			<!-- Menu Section -->
			<section id="tab-menu" class="tab-content">
				<h3>Menu & Toolbar</h3>
				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="hide_wp_logo" value="1" <?php checked( $settings['menu']['hide_wp_logo'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Hide WordPress Logo in Toolbar</span>
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="hide_help_tabs" value="1" <?php checked( $settings['menu']['hide_help_tabs'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Hide Help Tabs Across Admin Screens</span>
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="hide_screen_options" value="1" <?php checked( $settings['menu']['hide_screen_options'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Hide Screen Options Dropdown</span>
				</div>

				<hr style="border:0; border-top: 1px solid #eee; margin:30px 0;">
				
				<h3>Hide Sidebar Items</h3>
				<p>Select which core items to hide for non-admin users.</p>
				<div class="luxe-checkbox-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
					<?php 
					$core_menus = [
						'edit.php' => 'Posts',
						'upload.php' => 'Media',
						'edit.php?post_type=page' => 'Pages',
						'edit-comments.php' => 'Comments',
						'themes.php' => 'Appearance',
						'plugins.php' => 'Plugins',
						'users.php' => 'Users',
						'tools.php' => 'Tools',
						'options-general.php' => 'Settings',
					];
					foreach ( $core_menus as $slug => $label ) :
						$checked = in_array( $slug, $settings['menu']['hidden'], true );
					?>
						<label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
							<input type="checkbox" name="hide_menu[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $checked ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Dashboard Section -->
			<section id="tab-dashboard" class="tab-content">
				<h3>Dashboard Workspace</h3>
				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="hide_default_widgets" value="1" <?php checked( $settings['dashboard']['hide_default'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Hide All Default WP Widgets</span>
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="hide_welcome_panel" value="1" <?php checked( $settings['dashboard']['hide_welcome_panel'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Hide WordPress Welcome Panel</span>
				</div>

				<div class="luxe-field-group">
					<label>Welcome Message</label>
					<textarea name="welcome_msg"><?php echo esc_textarea( $settings['dashboard']['welcome_msg'] ); ?></textarea>
				</div>
			</section>

			<section id="tab-whitelabel" class="tab-content">
				<h3>Branding & White Label</h3>
				<p>Turn WordPress admin into a branded client control panel.</p>

				<div class="luxe-field-group stacked">
					<label>Brand Name</label>
					<input type="text" name="brand_name" value="<?php echo esc_attr( $settings['white_label']['brand_name'] ); ?>" placeholder="Jacana Safaris Dashboard">
				</div>

				<div class="luxe-field-group stacked">
					<label>Brand URL</label>
					<input type="text" name="brand_url" value="<?php echo esc_attr( $settings['white_label']['brand_url'] ); ?>" placeholder="https://example.com">
				</div>

				<div class="luxe-field-group stacked">
					<label>Admin Footer Text</label>
					<input type="text" name="footer_text" value="<?php echo esc_attr( $settings['white_label']['footer_text'] ); ?>" placeholder="Crafted for Jacana by Your Agency">
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="disable_admin_notices" value="1" <?php checked( $settings['white_label']['disable_admin_notices'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Disable Admin Notices</span>
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="disable_update_nag" value="1" <?php checked( $settings['white_label']['disable_update_nag'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Disable Core Update Nag</span>
				</div>

				<div class="luxe-field-group">
					<label class="switch">
						<input type="checkbox" name="hide_wp_version" value="1" <?php checked( $settings['white_label']['hide_wp_version'], 1 ); ?>>
						<span class="slider round"></span>
					</label>
					<span>Hide WordPress Version in Footer</span>
				</div>
			</section>
		</main>
	</div>
</div>
