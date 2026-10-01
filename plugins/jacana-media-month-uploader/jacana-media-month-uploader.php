<?php
/**
 * Plugin Name: Jacana Media Month Uploader
 * Description: Bulk upload media into a specific year/month folder, plus a full media audit and migration repair workflow.
 * Version: 1.3.1
 * Author: Winston Zulu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Jacana_Media_Month_Uploader {

	const MENU_SLUG                     = 'jacana-media-month-uploader';
	const OPTION_DEFAULT_MAX_FILES      = 'jmum_default_max_files';
	const OPTION_DEFAULT_DUPLICATE_MODE = 'jmum_default_duplicate_mode';
	const OPTION_AUDIT_BATCH_SIZE       = 'jmum_audit_batch_size';
	const DUPLICATE_MODE_SKIP           = 'skip';
	const DUPLICATE_MODE_ALLOW          = 'allow';
	const MIN_BATCH_LIMIT               = 1;
	const MAX_BATCH_LIMIT               = 5000;
	const NOTICE_TTL                    = 300;
	const AUDIT_DATA_TTL                = 7200;
	const MANIFEST_VERSION              = 1;
	const DEFAULT_AUDIT_BATCH_SIZE      = 20;

	private $forced_subdir            = '';
	private $hash_duplicate_cache     = array();
	private $name_size_duplicate_cache = array();

	public static function activate() {
		if ( false === get_option( self::OPTION_DEFAULT_MAX_FILES, false ) ) {
			add_option( self::OPTION_DEFAULT_MAX_FILES, 200 );
		}
		if ( false === get_option( self::OPTION_DEFAULT_DUPLICATE_MODE, false ) ) {
			add_option( self::OPTION_DEFAULT_DUPLICATE_MODE, self::DUPLICATE_MODE_SKIP );
		}
		if ( false === get_option( self::OPTION_AUDIT_BATCH_SIZE, false ) ) {
			add_option( self::OPTION_AUDIT_BATCH_SIZE, self::DEFAULT_AUDIT_BATCH_SIZE );
		}
	}

	public function __construct() {
		add_action( 'admin_menu',                         array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts',              array( $this, 'enqueue_assets' ) );
		add_filter( 'upload_dir',                         array( $this, 'filter_upload_dir' ) );
		add_action( 'admin_post_jmum_upload',             array( $this, 'handle_upload_post' ) );
		add_action( 'admin_post_jmum_save_settings',      array( $this, 'handle_settings_post' ) );
		add_action( 'admin_post_jmum_export_manifest',    array( $this, 'handle_export_manifest' ) );
		add_action( 'admin_post_jmum_import_manifest',    array( $this, 'handle_import_manifest' ) );
		add_action( 'admin_post_jmum_export_audit_report', array( $this, 'handle_export_audit_report' ) );
		add_action( 'admin_post_jmum_import_audit_report', array( $this, 'handle_import_audit_report' ) );
		add_action( 'admin_post_jmum_export_batch',       array( $this, 'handle_export_batch' ) );
		add_action( 'admin_post_jmum_import_zip',         array( $this, 'handle_import_zip' ) );
	}

	// -------------------------------------------------------------------------
	// Menu & assets
	// -------------------------------------------------------------------------

	public function register_menu() {
		add_media_page(
			'Media Month Uploader',
			'Bulk Upload by Month',
			'upload_files',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'media_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		$version     = '1.3.0';
		$default_max = $this->get_default_max_files();
		$php_max     = $this->get_php_max_files();
		$effective   = $this->resolve_effective_file_limit( $default_max, $php_max );

		wp_enqueue_style( 'jmum-admin', plugin_dir_url( __FILE__ ) . 'assets/jmum-admin.css', array(), $version );
		wp_enqueue_script( 'jmum-admin', plugin_dir_url( __FILE__ ) . 'assets/jmum-admin.js', array(), $version, true );

		wp_localize_script( 'jmum-admin', 'JmumConfig', array(
			'defaultMaxFiles' => $default_max,
			'phpMaxFiles'     => $php_max,
			'effectiveMax'    => $effective,
			'hasPhpCap'       => $php_max > 0,
			'messages'        => array(
				'filesSelected'        => 'Selected files:',
				'overLimit'            => 'Selection exceeded the limit. Extra files were skipped.',
				'dropPrompt'           => 'Drop files here or click to choose',
				'duplicateInSelection' => 'Duplicate files in this selection were collapsed.',
			),
		) );
	}

	public function filter_upload_dir( $uploads ) {
		if ( empty( $this->forced_subdir ) ) {
			return $uploads;
		}
		$uploads['subdir'] = $this->forced_subdir;
		$uploads['path']   = $uploads['basedir'] . $this->forced_subdir;
		$uploads['url']    = $uploads['baseurl'] . $this->forced_subdir;
		return $uploads;
	}

	// -------------------------------------------------------------------------
	// Page render
	// -------------------------------------------------------------------------

	public function render_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'You do not have permission to access this page.' );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'upload';
		$notice     = $this->consume_notice();
		$page_url   = $this->get_admin_page_url();
		?>
		<div class="wrap jmum-wrap">
			<h1>Media Month Uploader</h1>

			<?php $this->render_notice( $notice ); ?>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'upload', $page_url ) ); ?>"
				   class="nav-tab<?php echo 'upload' === $active_tab ? ' nav-tab-active' : ''; ?>">
					Bulk Upload
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'audit', $page_url ) ); ?>"
				   class="nav-tab<?php echo 'audit' === $active_tab ? ' nav-tab-active' : ''; ?>">
					Media Audit
				</a>
			</nav>

			<?php if ( 'audit' === $active_tab ) : ?>
				<?php $this->render_audit_tab(); ?>
			<?php else : ?>
				<?php $this->render_upload_tab(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Upload tab
	// -------------------------------------------------------------------------

	private function render_upload_tab() {
		$default_year           = (int) current_time( 'Y' );
		$default_month          = (int) current_time( 'n' );
		$max_allowed_year       = $default_year + 10;
		$default_max_files      = $this->get_default_max_files();
		$default_duplicate_mode = $this->get_default_duplicate_mode();
		$php_max_files          = $this->get_php_max_files();
		$effective_max          = $this->resolve_effective_file_limit( $default_max_files, $php_max_files );
		$upload_max_filesize    = ini_get( 'upload_max_filesize' );
		$post_max_size          = ini_get( 'post_max_size' );
		$php_max_label          = $php_max_files > 0 ? (string) $php_max_files : 'unlimited/unknown';
		?>
		<div class="jmum-card">
			<h2>Settings</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'jmum_save_settings', 'jmum_settings_nonce' ); ?>
				<input type="hidden" name="action" value="jmum_save_settings">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="jmum_default_max_files">Default max files per batch</label></th>
						<td>
							<input type="number" id="jmum_default_max_files" name="jmum_default_max_files"
							       min="<?php echo esc_attr( self::MIN_BATCH_LIMIT ); ?>"
							       max="<?php echo esc_attr( self::MAX_BATCH_LIMIT ); ?>"
							       value="<?php echo esc_attr( $default_max_files ); ?>" required>
							<p class="description">Adjustable per-batch below.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jmum_default_duplicate_mode">Default duplicate handling</label></th>
						<td>
							<select id="jmum_default_duplicate_mode" name="jmum_default_duplicate_mode">
								<option value="<?php echo esc_attr( self::DUPLICATE_MODE_SKIP ); ?>"
								        <?php selected( self::DUPLICATE_MODE_SKIP, $default_duplicate_mode ); ?>>
									Skip duplicates (recommended)
								</option>
								<option value="<?php echo esc_attr( self::DUPLICATE_MODE_ALLOW ); ?>"
								        <?php selected( self::DUPLICATE_MODE_ALLOW, $default_duplicate_mode ); ?>>
									Upload anyway
								</option>
							</select>
							<p class="description">Checks SHA1 hash and filename/size.</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save Settings', 'secondary', 'jmum_save_settings_submit', false ); ?>
			</form>
		</div>

		<div class="jmum-card">
			<h2>Upload Media</h2>
			<form method="post" enctype="multipart/form-data"
			      action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="jmum-upload-form">
				<?php wp_nonce_field( 'jmum_bulk_upload', 'jmum_nonce' ); ?>
				<input type="hidden" name="action" value="jmum_upload">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="jmum_year">Year</label></th>
						<td>
							<input type="number" id="jmum_year" name="jmum_year"
							       min="1970" max="<?php echo esc_attr( $max_allowed_year ); ?>"
							       value="<?php echo esc_attr( $default_year ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jmum_month">Month</label></th>
						<td>
							<select id="jmum_month" name="jmum_month" required>
								<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
									<option value="<?php echo esc_attr( $m ); ?>"
									        <?php selected( $m, $default_month ); ?>>
										<?php echo esc_html( gmdate( 'F', gmmktime( 0, 0, 0, $m, 1 ) ) ); ?>
									</option>
								<?php endfor; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jmum_batch_max_files">Batch file limit</label></th>
						<td>
							<input type="number" id="jmum_batch_max_files" name="jmum_batch_max_files"
							       min="<?php echo esc_attr( self::MIN_BATCH_LIMIT ); ?>"
							       max="<?php echo esc_attr( self::MAX_BATCH_LIMIT ); ?>"
							       value="<?php echo esc_attr( $default_max_files ); ?>" required>
							<p class="description">Effective limit: <strong id="jmum-effective-limit"><?php echo esc_html( (string) $effective_max ); ?></strong><?php echo $php_max_files > 0 ? esc_html( ' (capped by PHP max_file_uploads)' ) : ''; ?>.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jmum_duplicate_mode">Duplicate handling</label></th>
						<td>
							<select id="jmum_duplicate_mode" name="jmum_duplicate_mode">
								<option value="<?php echo esc_attr( self::DUPLICATE_MODE_SKIP ); ?>"
								        <?php selected( self::DUPLICATE_MODE_SKIP, $default_duplicate_mode ); ?>>
									Skip duplicates
								</option>
								<option value="<?php echo esc_attr( self::DUPLICATE_MODE_ALLOW ); ?>"
								        <?php selected( self::DUPLICATE_MODE_ALLOW, $default_duplicate_mode ); ?>>
									Upload anyway
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Safety</th>
						<td>
							<label>
								<input type="checkbox" name="jmum_dry_run" value="1">
								Dry run — validate without uploading
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="jmum_files">Files</label></th>
						<td>
							<div id="jmum-drop-zone" class="jmum-drop-zone" tabindex="0"
							     role="button" aria-label="Drop files here or click to choose">
								<p><strong>Drop files here</strong> or click to choose</p>
								<p class="description">Images, PDFs, and videos accepted.</p>
							</div>
							<input type="file" id="jmum_files" name="jmum_files[]" multiple
							       accept="image/*,application/pdf,video/*" required class="jmum-file-input">
							<div class="jmum-meta" id="jmum-file-meta">Selected files: 0</div>
							<div class="jmum-warning" id="jmum-warning" hidden></div>
							<button type="button" class="button" id="jmum-clear-files">Clear Selection</button>
						</td>
					</tr>
				</table>

				<div class="jmum-server-limits">
					<strong>Server limits:</strong>
					max_file_uploads: <?php echo esc_html( $php_max_label ); ?>,
					upload_max_filesize: <?php echo esc_html( (string) $upload_max_filesize ); ?>,
					post_max_size: <?php echo esc_html( (string) $post_max_size ); ?>
				</div>

				<?php submit_button( 'Upload to Selected Month', 'primary', 'jmum_submit', false ); ?>
			</form>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Audit tab
	// -------------------------------------------------------------------------

	private function render_audit_tab() {
		$audit_results = $this->get_audit_results();
		$audit_report  = $this->get_audit_report();
		$batch_size    = $this->get_audit_batch_size();
		$issues_count  = 0;
		$audit_batches = 0;

		if ( ! empty( $audit_report['items'] ) && is_array( $audit_report['items'] ) ) {
			$issues_count  = count( $audit_report['items'] );
			$audit_batches = (int) ceil( $issues_count / $batch_size );
		}
		?>
		<div class="jmum-audit-grid">

			<!-- Step 1 -->
			<div class="jmum-card jmum-audit-step">
				<div class="jmum-step-badge">Step 1</div>
				<h2>Export Manifest <span class="jmum-site-role">Source Site</span></h2>
				<p>Generate a JSON manifest of all media in this site's library (images, PDFs, videos). Run this on the <strong>source (original) site</strong>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'jmum_export_manifest', 'jmum_manifest_nonce' ); ?>
					<input type="hidden" name="action" value="jmum_export_manifest">
					<label>
						<input type="checkbox" name="jmum_include_sha1" value="1">
						Include SHA1 hash (slower but enables corruption detection)
					</label>
					<br><br>
					<?php submit_button( 'Export Manifest JSON', 'primary', 'jmum_export_manifest_submit', false ); ?>
				</form>
			</div>

			<!-- Step 2 -->
			<div class="jmum-card jmum-audit-step">
				<div class="jmum-step-badge">Step 2</div>
				<h2>Import Manifest &amp; Audit <span class="jmum-site-role">Destination Site</span></h2>
				<p>Upload the manifest from Step 1. This site will compare it against its own media library and flag files as <strong>OK</strong>, <strong>Missing</strong>, or <strong>Corrupted</strong>.</p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'jmum_import_manifest', 'jmum_import_manifest_nonce' ); ?>
					<input type="hidden" name="action" value="jmum_import_manifest">
					<input type="file" name="jmum_manifest_file" accept="application/json,.json" required>
					<br><br>
					<?php submit_button( 'Run Audit', 'primary', 'jmum_import_manifest_submit', false ); ?>
				</form>

				<?php if ( ! empty( $audit_results ) ) : ?>
					<?php $this->render_audit_results( $audit_results ); ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'jmum_export_audit_report', 'jmum_export_audit_report_nonce' ); ?>
						<input type="hidden" name="action" value="jmum_export_audit_report">
						<?php submit_button( 'Export Audit Report JSON', 'secondary', 'jmum_export_audit_report_submit', false ); ?>
					</form>
				<?php endif; ?>
			</div>

			<!-- Step 3 -->
			<div class="jmum-card jmum-audit-step">
				<div class="jmum-step-badge">Step 3</div>
				<h2>Import Report &amp; Export Batches <span class="jmum-site-role">Source Site</span></h2>
				<p>Upload the audit report from Step 2. Missing and corrupted files will be packaged into ZIP batches of <strong><?php echo esc_html( (string) $batch_size ); ?></strong> files each.</p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'jmum_import_audit_report', 'jmum_import_audit_report_nonce' ); ?>
					<input type="hidden" name="action" value="jmum_import_audit_report">
					<input type="file" name="jmum_audit_report_file" accept="application/json,.json" required>
					<br><br>
					<?php submit_button( 'Load Audit Report', 'primary', 'jmum_import_audit_report_submit', false ); ?>
				</form>

				<?php if ( $audit_batches > 0 ) : ?>
					<div class="jmum-batch-info">
						<p>
							<strong><?php echo esc_html( (string) $issues_count ); ?></strong> file(s) to restore across
							<strong><?php echo esc_html( (string) $audit_batches ); ?></strong> batch(es).
						</p>
						<div class="jmum-batch-grid">
							<?php for ( $i = 0; $i < $audit_batches; $i++ ) :
								$start = $i * $batch_size + 1;
								$end   = min( ( $i + 1 ) * $batch_size, $issues_count );
								?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'jmum_export_batch', 'jmum_export_batch_nonce' ); ?>
									<input type="hidden" name="action" value="jmum_export_batch">
									<input type="hidden" name="jmum_batch_index" value="<?php echo esc_attr( (string) $i ); ?>">
									<button type="submit" class="button button-secondary jmum-batch-btn">
										Batch <?php echo esc_html( (string) ( $i + 1 ) ); ?>
										<span class="jmum-batch-range"><?php echo esc_html( $start . '–' . $end ); ?></span>
									</button>
								</form>
							<?php endfor; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- Step 4 -->
			<div class="jmum-card jmum-audit-step">
				<div class="jmum-step-badge">Step 4</div>
				<h2>Import ZIP Batch <span class="jmum-site-role">Destination Site</span></h2>
				<p>Upload each ZIP batch from Step 3. Files will be placed in the correct locations and attachment records will be created or updated automatically.</p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'jmum_import_zip', 'jmum_import_zip_nonce' ); ?>
					<input type="hidden" name="action" value="jmum_import_zip">
					<input type="file" name="jmum_zip_file" accept=".zip" required>
					<br><br>
					<?php submit_button( 'Import ZIP Batch', 'primary', 'jmum_import_zip_submit', false ); ?>
				</form>
			</div>

		</div>
		<?php
	}

	private function render_audit_results( $results ) {
		$counts = array( 'ok' => 0, 'missing' => 0, 'corrupted' => 0 );
		foreach ( $results as $item ) {
			$s = isset( $item['status'] ) ? $item['status'] : 'ok';
			if ( isset( $counts[ $s ] ) ) {
				$counts[ $s ]++;
			}
		}
		?>
		<div class="jmum-audit-stats">
			<div class="jmum-stat-box jmum-stat-ok">
				<span class="jmum-stat-num"><?php echo esc_html( (string) $counts['ok'] ); ?></span>
				<span class="jmum-stat-label">OK</span>
			</div>
			<div class="jmum-stat-box jmum-stat-missing">
				<span class="jmum-stat-num"><?php echo esc_html( (string) $counts['missing'] ); ?></span>
				<span class="jmum-stat-label">Missing</span>
			</div>
			<div class="jmum-stat-box jmum-stat-corrupted">
				<span class="jmum-stat-num"><?php echo esc_html( (string) $counts['corrupted'] ); ?></span>
				<span class="jmum-stat-label">Corrupted</span>
			</div>
		</div>

		<?php
		$issues = array_values( array_filter( $results, function ( $item ) {
			return isset( $item['status'] ) && 'ok' !== $item['status'];
		} ) );

		if ( ! empty( $issues ) ) :
		?>
		<details class="jmum-audit-details">
			<summary>Show issues (<?php echo esc_html( (string) count( $issues ) ); ?>)</summary>
			<table class="wp-list-table widefat fixed striped jmum-audit-table">
				<thead>
					<tr>
						<th style="width:100px;">Status</th>
						<th style="width:90px;">Reason</th>
						<th>Source Path</th>
						<th style="width:120px;">Type</th>
						<th style="width:90px;">Src Size</th>
						<th style="width:90px;">Local Size</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $issues as $item ) :
						$local_size = (int) ( $item['local_filesize'] ?? 0 );
						$match_type = (string) ( $item['match_type'] ?? 'none' );
						$reason_labels = array(
							'none'    => 'Not in DB or disk',
							'db_only' => 'In DB, no file',
							'filename'=> 'Wrong path',
						);
						$reason = $reason_labels[$match_type] ?? ucfirst( $match_type );
					?>
					<tr>
						<td>
							<span class="jmum-badge jmum-badge-<?php echo esc_attr( $item['status'] ); ?>">
								<?php echo esc_html( ucfirst( $item['status'] ) ); ?>
							</span>
						</td>
						<td><small><?php echo esc_html( $reason ); ?></small></td>
						<td><code><?php echo esc_html( $item['path'] ?? '' ); ?></code></td>
						<td><?php echo esc_html( $item['mimeType'] ?? '' ); ?></td>
						<td><?php echo esc_html( $this->format_bytes( (int) ( $item['filesize'] ?? 0 ) ) ); ?></td>
						<td><?php echo esc_html( $local_size > 0 ? $this->format_bytes( $local_size ) : '—' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
		<?php endif;
	}

	// -------------------------------------------------------------------------
	// Handler: export manifest
	// -------------------------------------------------------------------------

	public function handle_export_manifest() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_manifest_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_manifest_nonce'] ) ), 'jmum_export_manifest' ) ) {
			wp_die( 'Security check failed.' );
		}

		$include_sha1 = isset( $_POST['jmum_include_sha1'] ) && '1' === (string) $_POST['jmum_include_sha1'];
		$uploads      = wp_get_upload_dir();
		$basedir      = trailingslashit( $uploads['basedir'] );
		$baseurl      = trailingslashit( $uploads['baseurl'] );

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_mime_type, p.post_title, pm.meta_value AS rel_path
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_attached_file'
			 WHERE p.post_type = 'attachment'
			   AND p.post_status IN ('inherit', 'private', 'publish')
			   AND (
			       p.post_mime_type LIKE 'image/%'
			       OR p.post_mime_type = 'application/pdf'
			       OR p.post_mime_type LIKE 'video/%'
			   )
			 ORDER BY p.ID ASC",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$items = array();
		foreach ( $rows as $row ) {
			$rel_path = ltrim( (string) $row['rel_path'], '/' );
			$abs_path = $basedir . $rel_path;
			$filesize = 0;
			$sha1     = '';
			$width    = 0;
			$height   = 0;

			if ( is_readable( $abs_path ) ) {
				$filesize = (int) filesize( $abs_path );
				if ( $include_sha1 ) {
					$sha1 = (string) sha1_file( $abs_path );
				}
				if ( 0 === strpos( (string) $row['post_mime_type'], 'image/' ) ) {
					$sz     = @getimagesize( $abs_path );
					$width  = is_array( $sz ) ? (int) $sz[0] : 0;
					$height = is_array( $sz ) ? (int) $sz[1] : 0;
				}
			}

			$items[] = array(
				'id'       => (int) $row['ID'],
				'filename' => wp_basename( $rel_path ),
				'path'     => $rel_path,
				'url'      => $baseurl . $rel_path,
				'mimeType' => (string) $row['post_mime_type'],
				'filesize' => $filesize,
				'sha1'     => $sha1,
				'width'    => $width,
				'height'   => $height,
			);
		}

		$payload = array(
			'version'     => self::MANIFEST_VERSION,
			'generatedAt' => gmdate( 'c' ),
			'siteUrl'     => home_url( '/' ),
			'totalItems'  => count( $items ),
			'items'       => $items,
		);

		$json        = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$filename_dl = 'jmum-manifest-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename_dl . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		echo $json;
		exit;
	}

	// -------------------------------------------------------------------------
	// Handler: import manifest & run audit
	// -------------------------------------------------------------------------

	public function handle_import_manifest() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_import_manifest_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_import_manifest_nonce'] ) ), 'jmum_import_manifest' ) ) {
			$this->set_notice( 'Security check failed.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		if ( empty( $_FILES['jmum_manifest_file']['tmp_name'] ) ) {
			$this->set_notice( 'No manifest file uploaded.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$raw      = @file_get_contents( $_FILES['jmum_manifest_file']['tmp_name'] );
		$manifest = is_string( $raw ) ? json_decode( $raw, true ) : null;

		if ( ! is_array( $manifest ) || empty( $manifest['items'] ) || ! is_array( $manifest['items'] ) ) {
			$this->set_notice( 'Invalid manifest file. Export a fresh manifest from the source site.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$uploads = wp_get_upload_dir();
		$basedir = trailingslashit( $uploads['basedir'] );

		global $wpdb;
		$local_rows = $wpdb->get_results(
			"SELECT pm.post_id, pm.meta_value AS rel_path
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_wp_attached_file'
			   AND p.post_type = 'attachment'
			   AND p.post_status IN ('inherit', 'private', 'publish')",
			ARRAY_A
		);

		// Primary lookup: exact relative path → post_id
		$local_map = array();
		// Fallback lookup: lowercase basename → array of [path, post_id]
		$local_by_name = array();

		foreach ( (array) $local_rows as $r ) {
			$k             = ltrim( (string) $r['rel_path'], '/' );
			$local_map[$k] = (int) $r['post_id'];
			$bn            = strtolower( wp_basename( $k ) );
			if ( ! isset( $local_by_name[$bn] ) ) {
				$local_by_name[$bn] = array();
			}
			$local_by_name[$bn][] = array( 'path' => $k, 'post_id' => (int) $r['post_id'] );
		}

		$results = array();

		foreach ( $manifest['items'] as $item ) {
			if ( ! is_array( $item ) || empty( $item['path'] ) ) {
				continue;
			}

			$rel_path = ltrim( (string) $item['path'], '/' );
			$abs_path = $basedir . $rel_path;
			$src_size = (int) ( $item['filesize'] ?? 0 );
			$src_sha1 = (string) ( $item['sha1'] ?? '' );

			$result = array(
				'id'             => (int) ( $item['id'] ?? 0 ),
				'filename'       => (string) ( $item['filename'] ?? wp_basename( $rel_path ) ),
				'path'           => $rel_path,
				'mimeType'       => (string) ( $item['mimeType'] ?? '' ),
				'filesize'       => $src_size,
				'sha1'           => $src_sha1,
				'local_post_id'  => 0,
				'local_filesize' => 0,
				'local_path'     => $rel_path,
				'match_type'     => 'none',
				'status'         => 'missing',
			);

			// --- Primary check: file at expected path ---
			if ( is_readable( $abs_path ) ) {
				$local_size              = (int) filesize( $abs_path );
				$result['local_post_id'] = $local_map[$rel_path] ?? 0;
				$result['local_filesize'] = $local_size;
				$result['match_type']    = 'exact';
				$result['status']        = $this->classify_file_integrity( $src_size, $src_sha1, $local_size, $abs_path );

			} else {
				// File is NOT on disk at expected path.
				// Record any DB post_id so ZIP import can update the record later.
				if ( isset( $local_map[$rel_path] ) ) {
					$result['local_post_id'] = $local_map[$rel_path];
					$result['match_type']    = 'db_only';
				}

				// Fallback: look for the same filename in a different subfolder.
				$bn         = strtolower( wp_basename( $rel_path ) );
				$candidates = $local_by_name[$bn] ?? array();
				$found      = false;

				foreach ( $candidates as $candidate ) {
					$cabs = $basedir . $candidate['path'];
					if ( ! is_readable( $cabs ) ) {
						continue;
					}
					$local_size              = (int) filesize( $cabs );
					$result['local_post_id'] = $candidate['post_id'];
					$result['local_path']    = $candidate['path'];
					$result['local_filesize'] = $local_size;
					$result['match_type']    = 'filename';
					$result['status']        = $this->classify_file_integrity( $src_size, $src_sha1, $local_size, $cabs );
					$found = true;
					break;
				}

				// If no on-disk match found (exact or filename), it is missing.
				if ( ! $found ) {
					$result['status'] = 'missing';
				}
			}

			$results[] = $result;
		}

		$this->save_audit_results( $results );

		$ok      = count( array_filter( $results, function ( $r ) { return $r['status'] === 'ok'; } ) );
		$missing = count( array_filter( $results, function ( $r ) { return $r['status'] === 'missing'; } ) );
		$corrupt = count( array_filter( $results, function ( $r ) { return $r['status'] === 'corrupted'; } ) );
		$total   = count( $results );

		$this->set_notice(
			sprintf( 'Audit complete: %d OK, %d missing, %d corrupted (of %d total).', $ok, $missing, $corrupt, $total ),
			array(),
			( $missing + $corrupt > 0 ) ? 'warning' : 'success'
		);
		$this->redirect_to_audit_tab();
	}

	private function classify_file_integrity( $src_size, $src_sha1, $local_size, $abs_path ) {
		if ( $local_size < 1 ) {
			return 'corrupted';
		}
		if ( $src_size > 0 ) {
			$diff = abs( $local_size - $src_size );
			$pct  = $diff / max( 1, $src_size );
			if ( $diff > 100 && $pct > 0.02 ) {
				return 'corrupted';
			}
		}
		if ( ! empty( $src_sha1 ) ) {
			return ( (string) sha1_file( $abs_path ) === $src_sha1 ) ? 'ok' : 'corrupted';
		}
		return 'ok';
	}

	// -------------------------------------------------------------------------
	// Handler: export audit report
	// -------------------------------------------------------------------------

	public function handle_export_audit_report() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_export_audit_report_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_export_audit_report_nonce'] ) ), 'jmum_export_audit_report' ) ) {
			wp_die( 'Security check failed.' );
		}

		$results = $this->get_audit_results();
		if ( empty( $results ) ) {
			wp_die( 'No audit results found. Run an audit first.' );
		}

		$issues = array_values( array_filter( $results, function ( $r ) {
			return isset( $r['status'] ) && 'ok' !== $r['status'];
		} ) );

		$payload = array(
			'version'     => self::MANIFEST_VERSION,
			'generatedAt' => gmdate( 'c' ),
			'totalIssues' => count( $issues ),
			'items'       => $issues,
		);

		$json     = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$filename = 'jmum-audit-report-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		echo $json;
		exit;
	}

	// -------------------------------------------------------------------------
	// Handler: import audit report (on source site)
	// -------------------------------------------------------------------------

	public function handle_import_audit_report() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_import_audit_report_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_import_audit_report_nonce'] ) ), 'jmum_import_audit_report' ) ) {
			$this->set_notice( 'Security check failed.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		if ( empty( $_FILES['jmum_audit_report_file']['tmp_name'] ) ) {
			$this->set_notice( 'No audit report file uploaded.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$raw    = @file_get_contents( $_FILES['jmum_audit_report_file']['tmp_name'] );
		$report = is_string( $raw ) ? json_decode( $raw, true ) : null;

		if ( ! is_array( $report ) || empty( $report['items'] ) || ! is_array( $report['items'] ) ) {
			$this->set_notice( 'Invalid audit report file.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$this->save_audit_report( $report );

		$this->set_notice(
			sprintf( 'Audit report loaded: %d file(s) to restore.', count( $report['items'] ) ),
			array(),
			'success'
		);
		$this->redirect_to_audit_tab();
	}

	// -------------------------------------------------------------------------
	// Handler: export ZIP batch (on source site)
	// -------------------------------------------------------------------------

	public function handle_export_batch() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_export_batch_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_export_batch_nonce'] ) ), 'jmum_export_batch' ) ) {
			wp_die( 'Security check failed.' );
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( 'ZipArchive PHP extension is required but not available on this server.' );
		}

		$report = $this->get_audit_report();
		if ( empty( $report['items'] ) ) {
			wp_die( 'No audit report loaded. Import an audit report first (Step 3).' );
		}

		$batch_index = isset( $_POST['jmum_batch_index'] ) ? (int) wp_unslash( $_POST['jmum_batch_index'] ) : 0;
		$batch_size  = $this->get_audit_batch_size();
		$slice       = array_slice( $report['items'], $batch_index * $batch_size, $batch_size );

		if ( empty( $slice ) ) {
			wp_die( 'No items in this batch.' );
		}

		$uploads = wp_get_upload_dir();
		$basedir = trailingslashit( $uploads['basedir'] );
		$tmp_zip = wp_tempnam( 'jmum-batch' ) . '.zip';
		$zip     = new ZipArchive();

		if ( true !== $zip->open( $tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			wp_die( 'Could not create ZIP archive.' );
		}

		$manifest_items = array();

		foreach ( $slice as $item ) {
			$rel_path = ltrim( (string) ( $item['path'] ?? '' ), '/' );
			if ( '' === $rel_path ) {
				continue;
			}
			$abs_path = $basedir . $rel_path;
			if ( is_readable( $abs_path ) ) {
				$zip->addFile( $abs_path, 'media/' . $rel_path );
				$manifest_items[] = $item;
			}
		}

		$zip->addFromString( 'manifest.json', wp_json_encode( array(
			'version'    => self::MANIFEST_VERSION,
			'batchIndex' => $batch_index,
			'items'      => $manifest_items,
		), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

		$zip->close();

		if ( ! file_exists( $tmp_zip ) ) {
			wp_die( 'Failed to generate ZIP file.' );
		}

		$filename = sprintf( 'jmum-batch-%d-%s.zip', $batch_index + 1, gmdate( 'Ymd' ) );

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $tmp_zip ) );
		readfile( $tmp_zip );
		@unlink( $tmp_zip );
		exit;
	}

	// -------------------------------------------------------------------------
	// Handler: import ZIP batch (on destination site)
	// -------------------------------------------------------------------------

	public function handle_import_zip() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_import_zip_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_import_zip_nonce'] ) ), 'jmum_import_zip' ) ) {
			$this->set_notice( 'Security check failed.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->set_notice( 'ZipArchive PHP extension is required but not available on this server.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}
		if ( empty( $_FILES['jmum_zip_file']['tmp_name'] ) ) {
			$this->set_notice( 'No ZIP file uploaded.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$zip = new ZipArchive();
		if ( true !== $zip->open( $_FILES['jmum_zip_file']['tmp_name'] ) ) {
			$this->set_notice( 'Could not open ZIP file.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$extract_dir = trailingslashit( get_temp_dir() ) . 'jmum-import-' . wp_generate_password( 10, false );
		wp_mkdir_p( $extract_dir );
		$zip->extractTo( $extract_dir );
		$zip->close();

		$manifest_path = $extract_dir . '/manifest.json';
		if ( ! file_exists( $manifest_path ) ) {
			$this->cleanup_dir( $extract_dir );
			$this->set_notice( 'ZIP does not contain a manifest.json. Export batches via Step 3.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$raw      = file_get_contents( $manifest_path );
		$manifest = is_string( $raw ) ? json_decode( $raw, true ) : null;

		if ( ! is_array( $manifest ) || empty( $manifest['items'] ) ) {
			$this->cleanup_dir( $extract_dir );
			$this->set_notice( 'Invalid manifest inside ZIP.', array(), 'error' );
			$this->redirect_to_audit_tab();
		}

		$uploads  = wp_get_upload_dir();
		$basedir  = trailingslashit( $uploads['basedir'] );
		$baseurl  = trailingslashit( $uploads['baseurl'] );
		$restored = 0;
		$created  = 0;
		$failed   = 0;
		$errors   = array();

		global $wpdb;

		foreach ( $manifest['items'] as $item ) {
			$rel_path = ltrim( (string) ( $item['path'] ?? '' ), '/' );
			if ( '' === $rel_path ) {
				continue;
			}

			$src_path  = $extract_dir . '/media/' . $rel_path;
			$dest_path = $basedir . $rel_path;

			if ( ! is_readable( $src_path ) ) {
				$failed++;
				$errors[] = 'Not found in ZIP: ' . $rel_path;
				continue;
			}

			wp_mkdir_p( dirname( $dest_path ) );

			if ( ! copy( $src_path, $dest_path ) ) {
				$failed++;
				$errors[] = 'Could not write file: ' . $rel_path;
				continue;
			}

			$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_wp_attached_file' AND pm.meta_value = %s
				   AND p.post_type = 'attachment' LIMIT 1",
				$rel_path
			) );

			if ( $existing_id > 0 ) {
				update_attached_file( $existing_id, $dest_path );
				$meta = wp_generate_attachment_metadata( $existing_id, $dest_path );
				if ( ! is_wp_error( $meta ) ) {
					wp_update_attachment_metadata( $existing_id, $meta );
				}
				$restored++;
			} else {
				$mime  = (string) ( $item['mimeType'] ?? wp_check_filetype( $rel_path )['type'] ?? '' );
				$title = isset( $item['filename'] ) ? $this->clean_title_from_filename( $item['filename'] ) : wp_basename( $rel_path );

				$att_id = wp_insert_attachment( array(
					'guid'           => $baseurl . $rel_path,
					'post_mime_type' => $mime,
					'post_title'     => $title,
					'post_content'   => '',
					'post_status'    => 'inherit',
				), $dest_path, 0, true );

				if ( is_wp_error( $att_id ) ) {
					$failed++;
					$errors[] = $att_id->get_error_message();
					continue;
				}

				update_attached_file( $att_id, $dest_path );
				$meta = wp_generate_attachment_metadata( $att_id, $dest_path );
				if ( ! is_wp_error( $meta ) ) {
					wp_update_attachment_metadata( $att_id, $meta );
				}
				$created++;
			}
		}

		$this->cleanup_dir( $extract_dir );

		$summary = sprintf(
			'ZIP import complete: %d restored, %d new attachment(s) created, %d failed.',
			$restored,
			$created,
			$failed
		);
		$this->set_notice( $summary, $errors, $failed > 0 ? 'warning' : 'success' );
		$this->redirect_to_audit_tab();
	}

	// -------------------------------------------------------------------------
	// Handler: settings save
	// -------------------------------------------------------------------------

	public function handle_settings_post() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_settings_nonce'] ) ), 'jmum_save_settings' ) ) {
			$this->set_notice( 'Security check failed while saving settings.', array(), 'error' );
			$this->redirect_to_page();
		}

		$raw_max  = isset( $_POST['jmum_default_max_files'] ) ? (int) wp_unslash( $_POST['jmum_default_max_files'] ) : $this->get_default_max_files();
		$setting  = $this->sanitize_batch_limit( $raw_max );
		$raw_mode = isset( $_POST['jmum_default_duplicate_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['jmum_default_duplicate_mode'] ) ) : self::DUPLICATE_MODE_SKIP;
		$mode     = $this->normalize_duplicate_mode( $raw_mode );

		update_option( self::OPTION_DEFAULT_MAX_FILES, $setting );
		update_option( self::OPTION_DEFAULT_DUPLICATE_MODE, $mode );

		$this->set_notice( sprintf( 'Settings saved. Default batch: %1$d file(s), duplicates: %2$s.', $setting, $mode ), array(), 'success' );
		$this->redirect_to_page();
	}

	// -------------------------------------------------------------------------
	// Handler: bulk upload
	// -------------------------------------------------------------------------

	public function handle_upload_post() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		if ( ! isset( $_POST['jmum_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jmum_nonce'] ) ), 'jmum_bulk_upload' ) ) {
			$this->set_notice( 'Security check failed. Please try again.', array(), 'error' );
			$this->redirect_to_page();
		}

		$year           = isset( $_POST['jmum_year'] ) ? (int) wp_unslash( $_POST['jmum_year'] ) : 0;
		$month          = isset( $_POST['jmum_month'] ) ? (int) wp_unslash( $_POST['jmum_month'] ) : 0;
		$batch_limit    = isset( $_POST['jmum_batch_max_files'] ) ? (int) wp_unslash( $_POST['jmum_batch_max_files'] ) : $this->get_default_max_files();
		$batch_limit    = $this->sanitize_batch_limit( $batch_limit );
		$duplicate_mode = isset( $_POST['jmum_duplicate_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['jmum_duplicate_mode'] ) ) : $this->get_default_duplicate_mode();
		$duplicate_mode = $this->normalize_duplicate_mode( $duplicate_mode );
		$dry_run        = isset( $_POST['jmum_dry_run'] ) && '1' === (string) wp_unslash( $_POST['jmum_dry_run'] );
		$current_year   = (int) current_time( 'Y' );
		$message_limit  = 60;
		$msg_overflow   = 0;
		$details        = array();

		if ( $year < 1970 || $year > $current_year + 10 || $month < 1 || $month > 12 ) {
			$this->set_notice( 'Invalid year/month selected.', array(), 'error' );
			$this->redirect_to_page();
		}

		if ( ! isset( $_FILES['jmum_files'] ) || empty( $_FILES['jmum_files']['name'] ) ) {
			$this->set_notice( 'Please select at least one file.', array(), 'error' );
			$this->redirect_to_page();
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$all_files       = $this->normalize_files_array( $_FILES['jmum_files'] );
		$files           = $this->remove_empty_files( $all_files );
		$php_max_files   = $this->get_php_max_files();
		$effective_limit = $this->resolve_effective_file_limit( $batch_limit, $php_max_files );
		$target_subdir   = sprintf( '/%04d/%02d', $year, $month );
		$attachment_day  = sprintf( '%04d-%02d-01 12:00:00', $year, $month );

		$stats = array(
			'received'                    => count( $files ),
			'considered'                  => 0,
			'uploaded'                    => 0,
			'dry_run_ready'               => 0,
			'skipped_limit'               => 0,
			'skipped_batch_duplicates'    => 0,
			'skipped_existing_duplicates' => 0,
			'found_existing_duplicates'   => 0,
			'invalid'                     => 0,
			'failed'                      => 0,
		);

		if ( empty( $files ) ) {
			$this->set_notice( 'No valid files were provided.', array(), 'error' );
			$this->redirect_to_page();
		}

		if ( count( $files ) > $effective_limit ) {
			$stats['skipped_limit'] = count( $files ) - $effective_limit;
			$files                  = array_slice( $files, 0, $effective_limit );
			$this->add_limited_message( $details, $msg_overflow, sprintf( 'Skipped %d file(s) — effective batch limit is %d.', $stats['skipped_limit'], $effective_limit ), $message_limit );
		}

		$batch_seen_hashes    = array();
		$batch_seen_name_size = array();
		$this->hash_duplicate_cache      = array();
		$this->name_size_duplicate_cache = array();

		if ( ! $dry_run ) {
			$this->forced_subdir = $target_subdir;
		}

		try {
			foreach ( $files as $file ) {
				$stats['considered']++;
				$file_name = isset( $file['name'] ) ? (string) $file['name'] : '(unknown)';
				$file_size = isset( $file['size'] ) ? (int) $file['size'] : 0;

				if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
					$stats['invalid']++;
					$this->add_limited_message( $details, $msg_overflow, sprintf( '%s: %s', $file_name, $this->upload_error_message( isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE ) ), $message_limit );
					continue;
				}

				if ( $file_size < 1 ) {
					$stats['invalid']++;
					$this->add_limited_message( $details, $msg_overflow, sprintf( '%s: file appears empty.', $file_name ), $message_limit );
					continue;
				}

				$check = wp_check_filetype_and_ext( $file['tmp_name'], $file_name );
				$mime  = (string) ( $check['type'] ?? '' );

				$allowed = (
					0 === strpos( $mime, 'image/' ) ||
					0 === strpos( $mime, 'video/' ) ||
					'application/pdf' === $mime
				);

				if ( empty( $mime ) || ! $allowed ) {
					$stats['invalid']++;
					$this->add_limited_message( $details, $msg_overflow, sprintf( '%s: file type not allowed (images, PDFs, and videos only).', $file_name ), $message_limit );
					continue;
				}

				$file_hash     = $this->compute_file_hash( $file['tmp_name'] );
				$name_size_key = strtolower( sanitize_file_name( wp_basename( $file_name ) ) ) . '|' . $file_size;

				$batch_dup = false;
				if ( ! empty( $file_hash ) ) {
					$batch_dup = isset( $batch_seen_hashes[$file_hash] );
					$batch_seen_hashes[$file_hash] = true;
				} else {
					$batch_dup = isset( $batch_seen_name_size[$name_size_key] );
				}
				$batch_seen_name_size[$name_size_key] = true;

				if ( $batch_dup && self::DUPLICATE_MODE_SKIP === $duplicate_mode ) {
					$stats['skipped_batch_duplicates']++;
					$this->add_limited_message( $details, $msg_overflow, sprintf( '%s: skipped duplicate from this batch.', $file_name ), $message_limit );
					continue;
				}

				$existing_id = $this->find_existing_duplicate_attachment_id( $file_name, $file['tmp_name'], $file_size, $file_hash );
				if ( $existing_id > 0 ) {
					$stats['found_existing_duplicates']++;
					if ( self::DUPLICATE_MODE_SKIP === $duplicate_mode ) {
						$stats['skipped_existing_duplicates']++;
						$this->add_limited_message( $details, $msg_overflow, sprintf( '%1$s: skipped, duplicate of attachment #%2$d.', $file_name, $existing_id ), $message_limit );
						continue;
					}
				}

				if ( $dry_run ) {
					$stats['dry_run_ready']++;
					continue;
				}

				$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

				if ( isset( $upload['error'] ) ) {
					$stats['failed']++;
					$this->add_limited_message( $details, $msg_overflow, sprintf( '%s: %s', $file_name, $upload['error'] ), $message_limit );
					continue;
				}

				$attachment_id = wp_insert_attachment( array(
					'guid'           => $upload['url'],
					'post_mime_type' => $upload['type'],
					'post_title'     => $this->clean_title_from_filename( $file_name ),
					'post_content'   => '',
					'post_status'    => 'inherit',
					'post_date'      => $attachment_day,
					'post_date_gmt'  => get_gmt_from_date( $attachment_day ),
				), $upload['file'], 0, true );

				if ( is_wp_error( $attachment_id ) ) {
					$stats['failed']++;
					$this->add_limited_message( $details, $msg_overflow, sprintf( '%s: %s', $file_name, $attachment_id->get_error_message() ), $message_limit );
					continue;
				}

				update_attached_file( $attachment_id, $upload['file'] );

				$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
				if ( ! is_wp_error( $metadata ) ) {
					wp_update_attachment_metadata( $attachment_id, $metadata );
				}

				if ( ! empty( $file_hash ) ) {
					update_post_meta( $attachment_id, '_jmum_file_sha1', $file_hash );
				}
				update_post_meta( $attachment_id, '_jmum_source_name', sanitize_file_name( $file_name ) );
				update_post_meta( $attachment_id, '_jmum_import_period', sprintf( '%04d-%02d', $year, $month ) );

				$stats['uploaded']++;
			}
		} finally {
			$this->forced_subdir = '';
		}

		if ( $msg_overflow > 0 ) {
			$details[] = sprintf( 'Plus %d more message(s) not shown.', $msg_overflow );
		}

		$period_label = sprintf( '%04d/%02d', $year, $month );

		if ( $dry_run ) {
			$summary = sprintf( 'Dry run for %1$s: %2$d checked, %3$d ready, %4$d existing duplicates, %5$d invalid.', $period_label, $stats['considered'], $stats['dry_run_ready'], $stats['found_existing_duplicates'], $stats['invalid'] );
			$this->set_notice( $summary, $details, ( $stats['invalid'] > 0 || $stats['skipped_limit'] > 0 ) ? 'warning' : 'success' );
			$this->redirect_to_page();
		}

		if ( 0 === $stats['uploaded'] ) {
			$summary = sprintf( 'No files uploaded for %1$s. Existing duplicates skipped: %2$d, batch duplicates: %3$d, invalid/failed: %4$d.', $period_label, $stats['skipped_existing_duplicates'], $stats['skipped_batch_duplicates'], $stats['invalid'] + $stats['failed'] );
			$this->set_notice( $summary, $details, 'error' );
			$this->redirect_to_page();
		}

		$summary = sprintf( 'Uploaded %1$d file(s) to %2$s. Duplicates skipped: %3$d, invalid: %4$d, failed: %5$d.', $stats['uploaded'], $period_label, $stats['skipped_existing_duplicates'] + $stats['skipped_batch_duplicates'], $stats['invalid'], $stats['failed'] );
		$this->set_notice( $summary, $details, empty( $details ) ? 'success' : 'warning' );
		$this->redirect_to_page();
	}

	// -------------------------------------------------------------------------
	// Audit data helpers
	// -------------------------------------------------------------------------

	private function audit_key( $suffix ) {
		return 'jmum_audit_' . get_current_user_id() . '_' . $suffix;
	}

	private function save_audit_results( $results ) {
		set_transient( $this->audit_key( 'results' ), $results, self::AUDIT_DATA_TTL );
	}

	private function get_audit_results() {
		$data = get_transient( $this->audit_key( 'results' ) );
		return is_array( $data ) ? $data : array();
	}

	private function save_audit_report( $report ) {
		set_transient( $this->audit_key( 'report' ), $report, self::AUDIT_DATA_TTL );
	}

	private function get_audit_report() {
		$data = get_transient( $this->audit_key( 'report' ) );
		return is_array( $data ) ? $data : array();
	}

	private function get_audit_batch_size() {
		$v = (int) get_option( self::OPTION_AUDIT_BATCH_SIZE, self::DEFAULT_AUDIT_BATCH_SIZE );
		return max( 1, min( 100, $v ) );
	}

	// -------------------------------------------------------------------------
	// Shared upload helpers (unchanged from 1.2.x)
	// -------------------------------------------------------------------------

	private function sanitize_batch_limit( $limit ) {
		$limit = (int) $limit;
		return max( self::MIN_BATCH_LIMIT, min( self::MAX_BATCH_LIMIT, $limit ) );
	}

	private function get_default_max_files() {
		return $this->sanitize_batch_limit( (int) get_option( self::OPTION_DEFAULT_MAX_FILES, 200 ) );
	}

	private function get_default_duplicate_mode() {
		return $this->normalize_duplicate_mode( (string) get_option( self::OPTION_DEFAULT_DUPLICATE_MODE, self::DUPLICATE_MODE_SKIP ) );
	}

	private function normalize_duplicate_mode( $mode ) {
		return self::DUPLICATE_MODE_ALLOW === sanitize_key( $mode ) ? self::DUPLICATE_MODE_ALLOW : self::DUPLICATE_MODE_SKIP;
	}

	private function get_php_max_files() {
		$v = (int) ini_get( 'max_file_uploads' );
		return $v < 1 ? 0 : $v;
	}

	private function resolve_effective_file_limit( $manual_limit, $php_limit ) {
		$manual = $this->sanitize_batch_limit( (int) $manual_limit );
		$php    = (int) $php_limit;
		return $php > 0 ? min( $manual, $php ) : $manual;
	}

	private function remove_empty_files( $files ) {
		return array_values( array_filter( $files, function ( $f ) {
			return isset( $f['name'] ) && '' !== (string) $f['name'];
		} ) );
	}

	private function normalize_files_array( $file_post ) {
		if ( ! is_array( $file_post['name'] ) ) {
			return array( $file_post );
		}
		$files = array();
		foreach ( array_keys( $file_post['name'] ) as $i ) {
			$files[] = array(
				'name'     => $file_post['name'][$i],
				'type'     => $file_post['type'][$i],
				'tmp_name' => $file_post['tmp_name'][$i],
				'error'    => $file_post['error'][$i],
				'size'     => $file_post['size'][$i],
			);
		}
		return $files;
	}

	private function clean_title_from_filename( $filename ) {
		$name = pathinfo( $filename, PATHINFO_FILENAME );
		return trim( preg_replace( '/[_-]+/', ' ', sanitize_text_field( wp_basename( $name ) ) ) );
	}

	private function compute_file_hash( $tmp_path ) {
		if ( empty( $tmp_path ) || ! is_readable( $tmp_path ) ) {
			return '';
		}
		$hash = @sha1_file( $tmp_path );
		return false === $hash ? '' : (string) $hash;
	}

	private function find_existing_duplicate_attachment_id( $file_name, $tmp_path, $file_size, $file_hash ) {
		$file_size = (int) $file_size;
		if ( $file_size < 1 ) {
			return 0;
		}

		$basename = sanitize_file_name( wp_basename( (string) $file_name ) );
		if ( '' === $basename ) {
			return 0;
		}

		if ( ! empty( $file_hash ) && array_key_exists( $file_hash, $this->hash_duplicate_cache ) ) {
			return (int) $this->hash_duplicate_cache[$file_hash];
		}

		$name_size_key = strtolower( $basename ) . '|' . $file_size;
		if ( array_key_exists( $name_size_key, $this->name_size_duplicate_cache ) ) {
			return (int) $this->name_size_duplicate_cache[$name_size_key];
		}

		if ( ! empty( $file_hash ) ) {
			$ids = get_posts( array(
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_key'       => '_jmum_file_sha1',
				'meta_value'     => $file_hash,
			) );
			if ( ! empty( $ids ) ) {
				$match                                     = (int) $ids[0];
				$this->hash_duplicate_cache[$file_hash]    = $match;
				$this->name_size_duplicate_cache[$name_size_key] = $match;
				return $match;
			}
		}

		global $wpdb;
		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['basedir'] ) ) {
			return 0;
		}

		$like_suffix = '%/' . $wpdb->esc_like( $basename );
		$candidates  = $wpdb->get_results( $wpdb->prepare(
			"SELECT pm.post_id, pm.meta_value
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_wp_attached_file'
			   AND p.post_type = 'attachment'
			   AND p.post_status IN ('inherit', 'private', 'publish')
			   AND (pm.meta_value = %s OR pm.meta_value LIKE %s)
			 LIMIT 80",
			$basename,
			$like_suffix
		), ARRAY_A );

		$match_id = 0;
		$basedir  = trailingslashit( $uploads['basedir'] );

		foreach ( (array) $candidates as $row ) {
			if ( empty( $row['meta_value'] ) ) {
				continue;
			}
			$abs = $basedir . ltrim( (string) $row['meta_value'], '/' );
			if ( ! is_readable( $abs ) ) {
				continue;
			}
			if ( (int) @filesize( $abs ) !== $file_size ) {
				continue;
			}
			if ( ! empty( $file_hash ) && @sha1_file( $abs ) !== $file_hash ) {
				continue;
			}
			$match_id = (int) $row['post_id'];
			break;
		}

		if ( ! empty( $file_hash ) ) {
			$this->hash_duplicate_cache[$file_hash] = $match_id;
		}
		$this->name_size_duplicate_cache[$name_size_key] = $match_id;

		return $match_id;
	}

	private function upload_error_message( $code ) {
		$messages = array(
			UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload size limit.',
			UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
			UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
			UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary upload folder.',
			UPLOAD_ERR_CANT_WRITE => 'Server could not write file to disk.',
			UPLOAD_ERR_EXTENSION  => 'File upload blocked by a PHP extension.',
		);
		return $messages[$code] ?? 'Unknown upload error.';
	}

	private function add_limited_message( &$messages, &$overflow, $message, $limit = 60 ) {
		if ( count( $messages ) < $limit ) {
			$messages[] = (string) $message;
		} else {
			$overflow++;
		}
	}

	// -------------------------------------------------------------------------
	// Notice helpers
	// -------------------------------------------------------------------------

	private function get_notice_key() {
		return 'jmum_notice_' . get_current_user_id();
	}

	private function set_notice( $summary, $errors = array(), $type = 'success' ) {
		set_transient( $this->get_notice_key(), array(
			'summary' => (string) $summary,
			'errors'  => array_values( array_map( 'strval', (array) $errors ) ),
			'type'    => in_array( $type, array( 'success', 'warning', 'error' ), true ) ? $type : 'success',
		), self::NOTICE_TTL );
	}

	private function consume_notice() {
		$key    = $this->get_notice_key();
		$notice = get_transient( $key );
		if ( false !== $notice ) {
			delete_transient( $key );
		}
		return is_array( $notice ) ? $notice : null;
	}

	private function render_notice( $notice ) {
		if ( empty( $notice['summary'] ) ) {
			return;
		}
		$class = array( 'success' => 'notice-success', 'warning' => 'notice-warning', 'error' => 'notice-error' );
		$type  = isset( $notice['type'] ) ? (string) $notice['type'] : 'success';
		?>
		<div class="notice <?php echo esc_attr( $class[$type] ?? 'notice-success' ); ?> is-dismissible">
			<p><?php echo esc_html( $notice['summary'] ); ?></p>
			<?php if ( ! empty( $notice['errors'] ) ) : ?>
				<ul style="margin-top:0;list-style:disc;padding-left:20px;">
					<?php foreach ( $notice['errors'] as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Utility
	// -------------------------------------------------------------------------

	private function format_bytes( $bytes ) {
		if ( $bytes < 1 ) return '0 B';
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$i     = 0;
		$v     = (float) $bytes;
		while ( $v >= 1024 && $i < count( $units ) - 1 ) {
			$v /= 1024;
			$i++;
		}
		return round( $v, $i === 0 ? 0 : 1 ) . ' ' . $units[$i];
	}

	private function cleanup_dir( $dir ) {
		if ( ! is_dir( $dir ) ) return;
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $it as $info ) {
			$fn = $info->isDir() ? 'rmdir' : 'unlink';
			@$fn( $info->getRealPath() );
		}
		@rmdir( $dir );
	}

	private function redirect_to_page() {
		wp_safe_redirect( $this->get_admin_page_url() );
		exit;
	}

	private function redirect_to_audit_tab() {
		wp_safe_redirect( add_query_arg( 'tab', 'audit', $this->get_admin_page_url() ) );
		exit;
	}

	private function get_admin_page_url() {
		return admin_url( 'upload.php?page=' . self::MENU_SLUG );
	}
}

register_activation_hook( __FILE__, array( 'Jacana_Media_Month_Uploader', 'activate' ) );
new Jacana_Media_Month_Uploader();
