<?php
/**
 * Affiliate Ads List Template
 * アフィリエイト広告一覧テンプレート
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ji-affiliate-admin">
    <h1 class="wp-heading-inline">アフィリエイト広告管理</h1>
    <a href="#" class="page-title-action ji-add-new-ad">新規追加</a>
    <hr class="wp-header-end">
    
    <?php if (!empty($ads)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>タイトル</th>
                    <th>タイプ</th>
                    <th>配置位置</th>
                    <th>デバイス</th>
                    <th>ステータス</th>
                    <th>優先度</th>
                    <th>開始日</th>
                    <th>終了日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $ad): ?>
                    <tr data-ad-id="<?php echo esc_attr($ad->id); ?>">
                        <td><?php echo esc_html($ad->id); ?></td>
                        <td>
                            <strong><?php echo esc_html($ad->title); ?></strong>
                        </td>
                        <td>
                            <?php 
                            $types = array(
                                'html' => 'HTML',
                                'image' => '画像',
                                'script' => 'スクリプト'
                            );
                            echo esc_html($types[$ad->ad_type] ?? $ad->ad_type);
                            ?>
                        </td>
                        <td>
                            <?php 
                            $position_labels = array(
                                // シングルページ - コラム
                                'single_column_sidebar_top' => 'コラム:SB上',
                                'single_column_sidebar_middle' => 'コラム:SB中',
                                'single_column_sidebar_bottom' => 'コラム:SB下',
                                'single_column_content_top' => 'コラム:本文上',
                                'single_column_content_middle' => 'コラム:本文中',
                                'single_column_content_bottom' => 'コラム:本文下',
                                // シングルページ - 補助金
                                'single_grant_sidebar_top' => '補助金:SB上',
                                'single_grant_sidebar_middle' => '補助金:SB中',
                                'single_grant_sidebar_bottom' => '補助金:SB下',
                                'single_grant_content_top' => '補助金:本文上',
                                'single_grant_content_middle' => '補助金:本文中',
                                'single_grant_content_bottom' => '補助金:本文下',
                                // アーカイブページ - 補助金
                                'archive_grant_sidebar_top' => '補助金AR:SB上',
                                'archive_grant_sidebar_middle' => '補助金AR:SB中',
                                'archive_grant_sidebar_bottom' => '補助金AR:SB下',
                                'archive_grant_content_top' => '補助金AR:本文上',
                                'archive_grant_content_bottom' => '補助金AR:本文下',
                                // アーカイブページ - コラム
                                'archive_column_sidebar_pr' => 'コラムAR:PR',
                                'archive_column_sidebar_top' => 'コラムAR:SB上',
                                'archive_column_sidebar_bottom' => 'コラムAR:SB下',
                                // フロントページ
                                'front_hero_bottom' => 'TOP:ヒーロー下',
                                'front_column_zone_top' => 'TOP:コラム上',
                                'front_column_zone_bottom' => 'TOP:コラム下',
                                'front_grant_news_top' => 'TOP:ニュース上',
                                'front_grant_news_bottom' => 'TOP:ニュース下',
                                'front_search_top' => 'TOP:検索上',
                                // 汎用
                                'sidebar_top' => 'SB上',
                                'sidebar_middle' => 'SB中',
                                'sidebar_bottom' => 'SB下',
                                'content_top' => '本文上',
                                'content_middle' => '本文中',
                                'content_bottom' => '本文下',
                                // 旧形式互換
                                'archive_sidebar_pr' => 'アーカイブPR'
                            );
                            
                            $ad_positions = isset($ad->positions) ? explode(',', $ad->positions) : array();
                            $display_positions = array();
                            foreach ($ad_positions as $pos) {
                                $pos = trim($pos);
                                $display_positions[] = $position_labels[$pos] ?? $pos;
                            }
                            echo esc_html(implode(', ', $display_positions));
                            ?>
                        </td>
                        <td>
                            <?php 
                            $devices = array(
                                'all' => 'すべて',
                                'desktop' => 'PC',
                                'mobile' => 'スマホ'
                            );
                            $device_target = isset($ad->device_target) ? $ad->device_target : 'all';
                            echo esc_html($devices[$device_target] ?? $device_target);
                            ?>
                        </td>
                        <td>
                            <span class="ji-status-badge <?php echo esc_attr($ad->status); ?>">
                                <?php 
                                $statuses = array(
                                    'active' => '有効',
                                    'inactive' => '無効',
                                    'draft' => '下書き'
                                );
                                echo esc_html($statuses[$ad->status] ?? $ad->status);
                                ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($ad->priority); ?></td>
                        <td><?php echo $ad->start_date ? esc_html(date('Y/m/d', strtotime($ad->start_date))) : '-'; ?></td>
                        <td><?php echo $ad->end_date ? esc_html(date('Y/m/d', strtotime($ad->end_date))) : '-'; ?></td>
                        <td>
                            <button class="button ji-edit-ad" data-ad-id="<?php echo esc_attr($ad->id); ?>">編集</button>
                            <button class="button ji-delete-ad" data-ad-id="<?php echo esc_attr($ad->id); ?>">削除</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>広告がまだありません。「新規追加」から最初の広告を作成してください。</p>
    <?php endif; ?>
</div>

<!-- 広告編集モーダル -->
<div id="ji-ad-modal" class="ji-modal" style="display: none;">
    <div class="ji-modal-content">
        <span class="ji-modal-close">&times;</span>
        <h2 id="ji-modal-title">広告を追加</h2>
        
        <form id="ji-ad-form">
            <input type="hidden" name="ad_id" id="ad_id" value="">
            
            <table class="form-table">
                <tr>
                    <th><label for="title">タイトル <span class="required">*</span></label></th>
                    <td><input type="text" name="title" id="title" class="regular-text" required></td>
                </tr>
                
                <tr>
                    <th><label for="ad_type">広告タイプ <span class="required">*</span></label></th>
                    <td>
                        <select name="ad_type" id="ad_type" required>
                            <option value="html">HTML</option>
                            <option value="image">画像</option>
                            <option value="script">スクリプト</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="content">広告コンテンツ <span class="required">*</span></label></th>
                    <td>
                        <textarea name="content" id="content" rows="10" class="large-text" required></textarea>
                        <p class="description">HTML、画像タグ、またはスクリプトコードを入力してください。</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="link_url">リンクURL</label></th>
                    <td><input type="url" name="link_url" id="link_url" class="regular-text"></td>
                </tr>
                
                <tr>
                    <th><label for="positions">配置位置 <span class="required">*</span></label></th>
                    <td>
                        <select name="positions[]" id="positions" multiple required style="height: 200px; width: 100%;">
                            <optgroup label="シングルページ - コラム">
                                <option value="single_column_sidebar_top">コラム: サイドバー上部</option>
                                <option value="single_column_sidebar_middle">コラム: サイドバー中央</option>
                                <option value="single_column_sidebar_bottom">コラム: サイドバー下部</option>
                                <option value="single_column_content_top">コラム: コンテンツ上部</option>
                                <option value="single_column_content_middle">コラム: コンテンツ中央</option>
                                <option value="single_column_content_bottom">コラム: コンテンツ下部</option>
                            </optgroup>
                            <optgroup label="シングルページ - 補助金">
                                <option value="single_grant_sidebar_top">補助金: サイドバー上部</option>
                                <option value="single_grant_sidebar_middle">補助金: サイドバー中央</option>
                                <option value="single_grant_sidebar_bottom">補助金: サイドバー下部</option>
                                <option value="single_grant_content_top">補助金: コンテンツ上部</option>
                                <option value="single_grant_content_middle">補助金: コンテンツ中央</option>
                                <option value="single_grant_content_bottom">補助金: コンテンツ下部</option>
                            </optgroup>
                            <optgroup label="アーカイブページ - 補助金">
                                <option value="archive_grant_sidebar_top">補助金アーカイブ: サイドバー上部</option>
                                <option value="archive_grant_sidebar_middle">補助金アーカイブ: サイドバー中央</option>
                                <option value="archive_grant_sidebar_bottom">補助金アーカイブ: サイドバー下部</option>
                                <option value="archive_grant_content_top">補助金アーカイブ: コンテンツ上部</option>
                                <option value="archive_grant_content_bottom">補助金アーカイブ: コンテンツ下部</option>
                            </optgroup>
                            <optgroup label="アーカイブページ - コラム">
                                <option value="archive_column_sidebar_pr">コラムアーカイブ: PR欄</option>
                                <option value="archive_column_sidebar_top">コラムアーカイブ: サイドバー上部</option>
                                <option value="archive_column_sidebar_bottom">コラムアーカイブ: サイドバー下部</option>
                            </optgroup>
                            <optgroup label="フロントページ">
                                <option value="front_hero_bottom">フロントページ: ヒーロー下部</option>
                                <option value="front_column_zone_top">フロントページ: コラムゾーン上部</option>
                                <option value="front_column_zone_bottom">フロントページ: コラムゾーン下部</option>
                                <option value="front_grant_news_top">フロントページ: 補助金ニュース上部</option>
                                <option value="front_grant_news_bottom">フロントページ: 補助金ニュース下部</option>
                                <option value="front_search_top">フロントページ: 検索エリア上部</option>
                            </optgroup>
                            <optgroup label="汎用">
                                <option value="sidebar_top">汎用: サイドバー上部</option>
                                <option value="sidebar_middle">汎用: サイドバー中央</option>
                                <option value="sidebar_bottom">汎用: サイドバー下部</option>
                                <option value="content_top">汎用: コンテンツ上部</option>
                                <option value="content_middle">汎用: コンテンツ中央</option>
                                <option value="content_bottom">汎用: コンテンツ下部</option>
                            </optgroup>
                        </select>
                        <p class="description">
                            <strong>複数選択可能:</strong> Ctrl（Windows）/ Command（Mac）キーを押しながらクリックして複数の位置を選択できます。<br>
                            選択した全ての位置に同じ広告が表示されます。
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="target_pages">対象ページ</label></th>
                    <td>
                        <select name="target_pages[]" id="target_pages" multiple style="height: 150px; width: 100%;">
                            <option value="">すべてのページ</option>
                            <optgroup label="シングルページ">
                                <option value="single-grant">補助金詳細ページ</option>
                                <option value="single-column">コラム詳細ページ</option>
                                <option value="single-post">投稿詳細ページ</option>
                                <option value="single-page">固定ページ</option>
                            </optgroup>
                            <optgroup label="アーカイブページ">
                                <option value="archive-grant">補助金アーカイブ</option>
                                <option value="archive-column">コラムアーカイブ</option>
                                <option value="archive">その他アーカイブ</option>
                            </optgroup>
                            <optgroup label="その他">
                                <option value="front-page">フロントページ</option>
                                <option value="search">検索結果ページ</option>
                                <option value="404">404ページ</option>
                            </optgroup>
                        </select>
                        <p class="description">
                            <strong>複数選択可能:</strong> Ctrl（Windows）/ Command（Mac）キーを押しながらクリックして複数選択できます。<br>
                            空白（選択なし）の場合、すべてのページに表示されます。
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="device_target">表示デバイス <span class="required">*</span></label></th>
                    <td>
                        <select name="device_target" id="device_target" required>
                            <option value="all">すべて（PC・スマホ）</option>
                            <option value="desktop">PCのみ</option>
                            <option value="mobile">スマートフォンのみ</option>
                        </select>
                        <p class="description">この広告を表示するデバイスを選択してください。</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="status">ステータス</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="active">有効</option>
                            <option value="inactive">無効</option>
                            <option value="draft">下書き</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="priority">優先度</label></th>
                    <td>
                        <input type="number" name="priority" id="priority" value="0" min="0" max="100">
                        <p class="description">数値が大きいほど優先的に表示されます（0-100）</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="start_date">開始日時</label></th>
                    <td><input type="datetime-local" name="start_date" id="start_date"></td>
                </tr>
                
                <tr>
                    <th><label for="end_date">終了日時</label></th>
                    <td><input type="datetime-local" name="end_date" id="end_date"></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">保存</button>
                <button type="button" class="button ji-modal-close">キャンセル</button>
            </p>
        </form>
    </div>
</div>

<style>
.ji-affiliate-admin {
    margin: 20px 20px 0 0;
}

.ji-status-badge {
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.ji-status-badge.active {
    background: #00a32a;
    color: white;
}

.ji-status-badge.inactive {
    background: #dcdcde;
    color: #50575e;
}

.ji-status-badge.draft {
    background: #f0f0f1;
    color: #2c3338;
}

.ji-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
}

.ji-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 30px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.ji-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}

.ji-modal-close:hover,
.ji-modal-close:focus {
    color: #000;
}

.required {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 新規追加
    $('.ji-add-new-ad').on('click', function(e) {
        e.preventDefault();
        $('#ji-modal-title').text('広告を追加');
        $('#ji-ad-form')[0].reset();
        $('#ad_id').val('');
        $('#ji-ad-modal').show();
    });
    
    // 編集
    $('.ji-edit-ad').on('click', function(e) {
        e.preventDefault();
        var adId = $(this).data('ad-id');
        
        $('#ji-modal-title').text('広告を編集');
        
        // AJAXで広告データを取得
        $.post(ajaxurl, {
            action: 'ji_get_ad',
            nonce: jiAdminAds.nonce,
            ad_id: adId
        }, function(response) {
            if (response.success) {
                var ad = response.data;
                
                // フォームに既存データを入力
                $('#ad_id').val(ad.id);
                $('#title').val(ad.title);
                $('#ad_type').val(ad.ad_type);
                $('#content').val(ad.content);
                $('#link_url').val(ad.link_url);
                
                // 配置位置（複数選択）
                $('#positions').val(ad.positions_array);
                
                // 対象ページ（複数選択）
                if (ad.target_pages_array && ad.target_pages_array.length > 0) {
                    $('#target_pages').val(ad.target_pages_array);
                } else {
                    $('#target_pages').val(['']); // すべてのページ
                }
                
                $('#device_target').val(ad.device_target || 'all');
                $('#status').val(ad.status);
                $('#priority').val(ad.priority);
                
                // 日時フィールド（datetime-local format: YYYY-MM-DDTHH:MM）
                if (ad.start_date) {
                    var startDate = new Date(ad.start_date);
                    $('#start_date').val(formatDateTimeLocal(startDate));
                }
                if (ad.end_date) {
                    var endDate = new Date(ad.end_date);
                    $('#end_date').val(formatDateTimeLocal(endDate));
                }
                
                $('#ji-ad-modal').show();
            } else {
                alert('エラー: ' + response.data);
            }
        });
    });
    
    // 日時をdatetime-local形式に変換
    function formatDateTimeLocal(date) {
        var year = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        var hours = ('0' + date.getHours()).slice(-2);
        var minutes = ('0' + date.getMinutes()).slice(-2);
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }
    
    // 削除
    $('.ji-delete-ad').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('この広告を削除してもよろしいですか？統計データも削除されます。')) {
            return;
        }
        
        var adId = $(this).data('ad-id');
        
        $.post(ajaxurl, {
            action: 'ji_delete_ad',
            nonce: jiAdminAds.nonce,
            ad_id: adId
        }, function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('エラー: ' + response.data);
            }
        });
    });
    
    // モーダルを閉じる
    $('.ji-modal-close').on('click', function() {
        $('#ji-ad-modal').hide();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).is('#ji-ad-modal')) {
            $('#ji-ad-modal').hide();
        }
    });
    
    // フォーム送信
    $('#ji-ad-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=ji_save_ad&nonce=' + jiAdminAds.nonce;
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('エラー: ' + response.data);
            }
        });
    });
});
</script>
