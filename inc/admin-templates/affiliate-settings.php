<?php
/**
 * Affiliate Settings Template
 * アフィリエイト広告設定テンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}

// 設定保存処理
if (isset($_POST['ji_save_settings']) && check_admin_referer('ji_affiliate_settings')) {
    update_option('ji_affiliate_tracking_enabled', isset($_POST['tracking_enabled']) ? '1' : '0');
    update_option('ji_affiliate_auto_optimize', isset($_POST['auto_optimize']) ? '1' : '0');
    update_option('ji_affiliate_cache_duration', intval($_POST['cache_duration']));
    
    echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
}

$tracking_enabled = get_option('ji_affiliate_tracking_enabled', '1');
$auto_optimize = get_option('ji_affiliate_auto_optimize', '0');
$cache_duration = get_option('ji_affiliate_cache_duration', '3600');
?>

<div class="wrap ji-affiliate-admin">
    <h1>アフィリエイト広告設定</h1>
    <hr class="wp-header-end">
    
    <form method="post" action="">
        <?php wp_nonce_field('ji_affiliate_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">統計追跡を有効化</th>
                <td>
                    <label>
                        <input type="checkbox" name="tracking_enabled" value="1" <?php checked($tracking_enabled, '1'); ?>>
                        広告の表示回数とクリック数を追跡する
                    </label>
                    <p class="description">チェックを外すと、統計データの収集を停止します。</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">自動最適化</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_optimize" value="1" <?php checked($auto_optimize, '1'); ?>>
                        CTRに基づいて広告を自動的に最適化する
                    </label>
                    <p class="description">パフォーマンスの高い広告を優先的に表示します。</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">キャッシュ時間（秒）</th>
                <td>
                    <input type="number" name="cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="0" max="86400" class="small-text">
                    <p class="description">広告をキャッシュする時間を秒で指定します（0=キャッシュなし、推奨: 3600）</p>
                </td>
            </tr>
        </table>
        
        <h2>広告位置について</h2>
        <p>以下の位置に広告を配置できます：</p>
        <ul style="list-style-type: disc; margin-left: 20px;">
            <li><strong>サイドバー上部</strong>: サイドバーの最上部（目次・AI相談の前）</li>
            <li><strong>サイドバー中央</strong>: サイドバーの中央部（目次とAI相談の間）</li>
            <li><strong>サイドバー下部</strong>: サイドバーの最下部（人気記事の後）</li>
            <li><strong>コンテンツ上部</strong>: 記事タイトルの直後</li>
            <li><strong>コンテンツ中央</strong>: 記事本文の途中</li>
            <li><strong>コンテンツ下部</strong>: 記事本文の直後</li>
        </ul>
        
        <h2>広告タイプについて</h2>
        <ul style="list-style-type: disc; margin-left: 20px;">
            <li><strong>HTML</strong>: 通常のHTMLコード（div、spanなど）</li>
            <li><strong>画像</strong>: imgタグまたは画像を含むHTML</li>
            <li><strong>スクリプト</strong>: Google AdSenseなどの外部スクリプト</li>
        </ul>
        
        <p class="submit">
            <input type="submit" name="ji_save_settings" class="button button-primary" value="設定を保存">
        </p>
    </form>
</div>
