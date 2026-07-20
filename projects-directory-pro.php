<?php
/**
 * Plugin Name: Projects Bank from VibesHolding Pro
 * Description: نظام بنك مشاريع احترافي من ڤايبز القابضة - ثنائي اللغة
 * Version: 32.0
 * Author: Yasser Dev
 * Author URI: https://vibesholding.com/
 * Text Domain: vibes-projects
 */
if (!defined('ABSPATH')) exit;

// ========== 1. ترجمة القطاعات ==========
function pdp_get_sector_translations() {
    return [
        'التكنولوجيا والبرمجيات' => 'Technology & Software',
        'الصحة والتكنولوجيا الحيوية' => 'Health & Biotechnology',
        'التعليم والتدريب' => 'Education & Training',
        'الطاقة والاستدامة' => 'Energy & Sustainability',
        'الخدمات المالية والتكنولوجيا المالية (FinTech)' => 'Financial Services & FinTech',
        'التجارة والتجزئة' => 'Commerce & Retail',
        'الاستشارات والتطوير الإداري' => 'Consulting & Management Development',
        'السياحة والضيافة' => 'Tourism & Hospitality',
        'الخدمات اللوجستية والنقل' => 'Logistics & Transportation',
        'الترفيه والإعلام' => 'Entertainment & Media',
        'الصناعة والتصنيع المتقدم' => 'Industry & Advanced Manufacturing',
        'العقار والتطوير العمراني' => 'Real Estate & Urban Development',
        'الأمن السيبراني وحلول البيانات' => 'Cybersecurity & Data Solutions',
        'الغذاء والمشروبات' => 'Food & Beverages',
        'أخرى' => 'Other',
    ];
}

// ========== 2. ترجمة المراحل ==========
function pdp_get_stage_translations() {
    return [
        'أخرى' => 'Other',
        'اخري' => 'Other',
        'فكرة' => 'Idea Stage',
        'مرحلة الفكرة' => 'Idea Stage',
        'بداية' => 'Early Stage',
        'مرحلة البداية' => 'Early Stage',
        'نمو' => 'Growth Stage',
        'مرحلة النمو' => 'Growth Stage',
        'توسع' => 'Expansion Stage',
        'مرحلة التوسع' => 'Expansion Stage',
        'مرحلة التوسع ( Expansion )' => 'Expansion Stage',
        'نضج' => 'Mature Stage',
        'مرحلة النضج' => 'Mature Stage',
        'مرحلة الفكرة ما قبل البذرة ( Pre-seed )' => 'Pre-seed Stage',
        'مرحلة المنتج الأولي البذرة ( seed )' => 'Seed Stage',
        'مرحلة تحقيق الدخل ( Cash Flow )' => 'Cash Flow Stage',
        'Pre-Seed' => 'Pre-Seed',
        'Seed' => 'Seed',
        'Series A' => 'Series A',
        'Series B' => 'Series B',
        'Series C' => 'Series C',
    ];
}

// ========== 3. دالة الترجمة الموحدة ==========
function pdp_translate_term($name, $type = 'sector', $lang = 'ar') {
    if ($lang === 'ar') return $name;
    $translations = ($type === 'sector') ? pdp_get_sector_translations() : pdp_get_stage_translations();
    if (isset($translations[$name])) return $translations[$name];
    if ($type === 'stage') {
        foreach ($translations as $ar => $en) {
            if (strpos($name, $ar) !== false || strpos($ar, $name) !== false) return $en;
        }
    }
    return $name;
}

// ========== 3.5 نظام التخزين المؤقت (Cache) ==========
function pdp_get_featured_ids_cached() {
    $cached = get_transient('pdp_featured_ids');
    if ($cached !== false) return $cached;
    $ids = get_posts([
        'post_type' => 'project',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_status' => 'publish',
        'meta_query' => [['key' => 'is_featured', 'value' => '1', 'compare' => '=']]
    ]);
    set_transient('pdp_featured_ids', $ids, HOUR_IN_SECONDS);
    return $ids;
}

add_action('save_post_project', 'pdp_clear_all_cache', 99);
function pdp_clear_all_cache($post_id) {
    delete_transient('pdp_featured_ids');
    delete_transient('pdp_sectors_0');
    delete_transient('pdp_sectors_1');
    delete_transient('pdp_stages_0');
    delete_transient('pdp_stages_1');
    delete_transient('pdp_total_views');
    delete_transient('pdp_admin_stats');
}

// ========== 4. تسجيل نوع المنشور والتصنيفات ==========
add_action('init', 'pdp_register_cpt');
function pdp_register_cpt() {
    register_post_type('project', [
        'labels' => [
            'name' => 'المشاريع',
            'singular_name' => 'مشروع',
            'add_new' => 'إضافة مشروع',
            'add_new_item' => 'إضافة مشروع جديد',
            'edit_item' => 'تعديل المشروع',
            'view_item' => 'عرض المشروع',
            'all_items' => 'جميع المشاريع',
            'search_items' => 'بحث في المشاريع',
        ],
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => 'projects'],
    ]);
    register_taxonomy('sector', 'project', [
        'labels' => ['name' => 'القطاعات', 'singular_name' => 'قطاع'],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
    register_taxonomy('project_stage', 'project', [
        'labels' => ['name' => 'مراحل المشروع', 'singular_name' => 'مرحلة'],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
}

// ========== 5. إضافة Terms افتراضية ==========
register_activation_hook(__FILE__, 'pdp_add_default_terms');
function pdp_add_default_terms() {
    $sectors = [
        'التكنولوجيا والبرمجيات','الصحة والتكنولوجيا الحيوية','التعليم والتدريب',
        'الطاقة والاستدامة','الخدمات المالية والتكنولوجيا المالية (FinTech)',
        'التجارة والتجزئة','الاستشارات والتطوير الإداري','السياحة والضيافة',
        'الخدمات اللوجستية والنقل','الترفيه والإعلام','الصناعة والتصنيع المتقدم',
        'العقار والتطوير العمراني','الأمن السيبراني وحلول البيانات',
        'الغذاء والمشروبات','أخرى'
    ];
    foreach ($sectors as $s) {
        if (!term_exists($s, 'sector')) wp_insert_term($s, 'sector');
    }
}

// ========== 6. Meta Box ==========
add_action('add_meta_boxes', 'pdp_add_meta_boxes');
function pdp_add_meta_boxes() {
    add_meta_box('pdp_details', '📝 تفاصيل المشروع - Project Details', 'pdp_render_meta_box', 'project', 'normal', 'high');
}

function pdp_render_meta_box($post) {
    wp_nonce_field('pdp_save_meta', 'pdp_meta_nonce');
    $founder_name = get_post_meta($post->ID, 'founder_name', true);
    $company_name = get_post_meta($post->ID, 'company_name', true);
    $whatsapp = get_post_meta($post->ID, 'whatsapp', true);
    $website = get_post_meta($post->ID, 'website', true);
    $email = get_post_meta($post->ID, 'email', true);
    $pitch_deck = get_post_meta($post->ID, 'pitch_deck', true);
    $project_details = get_post_meta($post->ID, 'project_details', true);
    $project_gallery = get_post_meta($post->ID, 'project_gallery', true);
    $is_featured = get_post_meta($post->ID, 'is_featured', true);
    $featured_order = get_post_meta($post->ID, 'featured_order', true);
    $title_en = get_post_meta($post->ID, 'title_en', true);
    $founder_name_en = get_post_meta($post->ID, 'founder_name_en', true);
    $company_name_en = get_post_meta($post->ID, 'company_name_en', true);
    $project_details_en = get_post_meta($post->ID, 'project_details_en', true);
    $excerpt_en = get_post_meta($post->ID, 'excerpt_en', true);
    ?>
    <style>
        .pdp-meta-tabs{display:flex;gap:0;margin-bottom:0;border-bottom:3px solid #1D2D51}.pdp-meta-tab{padding:14px 30px;cursor:pointer;background:#f5f5f5;border:none;font-size:1rem;font-weight:700;color:#666;transition:all .3s;border-radius:10px 10px 0 0;margin-left:-1px}.pdp-meta-tab:first-child{margin-left:0}.pdp-meta-tab.active{background:#1D2D51;color:#fff}.pdp-meta-tab.tab-ar.active{background:#1D2D51}.pdp-meta-tab.tab-en.active{background:#2563eb}.pdp-meta-tab:hover:not(.active){background:#e0e0e0}.pdp-tab-content{display:none;padding:25px;background:#fff;border:2px solid #e0e0e0;border-top:none;border-radius:0 0 12px 12px}.pdp-tab-content.active{display:block}.pdp-tab-content.tab-ar{direction:rtl;text-align:right}.pdp-tab-content.tab-en{direction:ltr;text-align:left}.pdp-meta-box{display:grid;grid-template-columns:1fr 1fr;gap:20px}.pdp-field{margin-bottom:15px}.pdp-field label{display:block;font-weight:600;margin-bottom:5px;color:#1D2D51}.pdp-field input,.pdp-field textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;transition:border-color .3s,box-shadow .3s}.pdp-field input:focus,.pdp-field textarea:focus{border-color:#C09E52;box-shadow:0 0 0 3px rgba(192,158,82,.1);outline:none}.pdp-field.full{grid-column:1/-1}.pdp-upload-btn{background:linear-gradient(135deg,#1D2D51,#2a3d6a);color:#fff;padding:12px 24px;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:transform .2s,box-shadow .2s}.pdp-upload-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(29,45,81,.3)}.pdp-file-preview{margin-top:10px;padding:12px 15px;background:linear-gradient(135deg,#f8f9fa,#fff);border-radius:8px;border:1px solid #e8e8e8}.pdp-gallery-preview{display:flex;flex-wrap:wrap;gap:12px;margin-top:12px}.pdp-gallery-item{position:relative}.pdp-gallery-item img{width:90px;height:90px;object-fit:cover;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1)}.pdp-gallery-item .remove-img{position:absolute;top:-8px;right:-8px;background:linear-gradient(135deg,#dc3545,#c82333);color:#fff;border:2px solid #fff;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:12px;box-shadow:0 2px 6px rgba(220,53,69,.4)}.pdp-featured-box{background:linear-gradient(135deg,#FEF9E7,#FDF2D5);border:2px solid #C9A961;border-radius:12px;padding:20px;margin-bottom:20px}.pdp-featured-box label{display:flex;align-items:center;gap:12px;cursor:pointer;font-size:1.1rem;font-weight:700;color:#1D2D51}.pdp-featured-box input[type="checkbox"]{width:22px;height:22px;accent-color:#C9A961;cursor:pointer}.pdp-featured-box .featured-desc{margin-top:8px;font-size:.85rem;color:#71788B;margin-right:34px}.pdp-featured-order-field{margin-top:15px;padding-top:15px;border-top:1px dashed #C9A961;display:none}.pdp-featured-order-field.show{display:block}.pdp-featured-order-field label{font-size:.9rem;font-weight:600;color:#1D2D51;margin-bottom:8px;display:block}.pdp-featured-order-field input{width:100px;padding:10px;border:2px solid #C9A961;border-radius:8px;font-size:1rem;font-weight:700;text-align:center}.pdp-lang-notice{background:linear-gradient(135deg,#e3f2fd,#bbdefb);border:2px solid #2196f3;border-radius:10px;padding:15px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px}.pdp-lang-notice svg{width:24px;height:24px;color:#1976d2;flex-shrink:0}.pdp-lang-notice p{margin:0;color:#1565c0;font-size:.9rem;line-height:1.6}
    </style>
    <div class="pdp-featured-box">
        <label>
            <input type="checkbox" name="is_featured" id="is_featured_checkbox" value="1" <?php checked($is_featured, '1'); ?>>
            <span>⭐</span><span>مشروع مميز / Featured Project</span>
        </label>
        <p class="featured-desc">سيظهر هذا المشروع بشكل بارز مع شارة فايبز القابضة الذهبية</p>
        <div class="pdp-featured-order-field <?php echo $is_featured === '1' ? 'show' : ''; ?>" id="featured_order_container">
            <label for="featured_order">ترتيب العرض (الرقم الأصغر يظهر أولاً)</label>
            <input type="number" name="featured_order" id="featured_order" value="<?php echo esc_attr($featured_order ?: '10'); ?>" min="1" max="999">
        </div>
    </div>
    <div class="pdp-meta-tabs">
        <button type="button" class="pdp-meta-tab tab-ar active" data-tab="ar">🇸🇦 العربية</button>
        <button type="button" class="pdp-meta-tab tab-en" data-tab="en">🇬🇧 English</button>
        <button type="button" class="pdp-meta-tab" data-tab="common">📎 ملفات مشتركة</button>
    </div>
    <div class="pdp-tab-content tab-ar active" id="tab-ar">
        <div class="pdp-meta-box">
            <div class="pdp-field"><label>اسم المؤسس</label><input type="text" name="founder_name" value="<?php echo esc_attr($founder_name); ?>" placeholder="أدخل اسم المؤسس"></div>
            <div class="pdp-field"><label>اسم الشركة</label><input type="text" name="company_name" value="<?php echo esc_attr($company_name); ?>" placeholder="أدخل اسم الشركة"></div>
            <div class="pdp-field full"><label>تفاصيل المشروع (عربي)</label><textarea name="project_details" rows="6" placeholder="اكتب وصفاً تفصيلياً للمشروع بالعربية..."><?php echo esc_textarea($project_details); ?></textarea></div>
        </div>
    </div>
    <div class="pdp-tab-content tab-en" id="tab-en">
        <div class="pdp-lang-notice">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p><strong>Note:</strong> Fill in the English fields to display the project on the English page. If left empty, the Arabic content will be used.</p>
        </div>
        <div class="pdp-meta-box">
            <div class="pdp-field full"><label>Project Title (English)</label><input type="text" name="title_en" value="<?php echo esc_attr($title_en); ?>" placeholder="Enter project title in English"></div>
            <div class="pdp-field"><label>Founder Name</label><input type="text" name="founder_name_en" value="<?php echo esc_attr($founder_name_en); ?>" placeholder="Enter founder name"></div>
            <div class="pdp-field"><label>Company Name</label><input type="text" name="company_name_en" value="<?php echo esc_attr($company_name_en); ?>" placeholder="Enter company name"></div>
            <div class="pdp-field full"><label>Short Description (Excerpt)</label><textarea name="excerpt_en" rows="3" placeholder="Brief description for the project card..."><?php echo esc_textarea($excerpt_en); ?></textarea></div>
            <div class="pdp-field full"><label>Project Details (English)</label><textarea name="project_details_en" rows="6" placeholder="Write detailed project description in English..."><?php echo esc_textarea($project_details_en); ?></textarea></div>
        </div>
    </div>
    <div class="pdp-tab-content" id="tab-common" style="direction:rtl;text-align:right;">
        <div class="pdp-meta-box">
            <div class="pdp-field"><label>واتساب / WhatsApp</label><input type="text" name="whatsapp" value="<?php echo esc_attr($whatsapp); ?>" placeholder="+966xxxxxxxxx" dir="ltr"></div>
            <div class="pdp-field"><label>الموقع الإلكتروني / Website</label><input type="url" name="website" value="<?php echo esc_attr($website); ?>" placeholder="https://example.com" dir="ltr"></div>
            <div class="pdp-field"><label>البريد الإلكتروني / Email</label><input type="email" name="email" value="<?php echo esc_attr($email); ?>" placeholder="email@example.com" dir="ltr"></div>
            <div class="pdp-field">
                <label>Pitch Deck (PDF)</label>
                <input type="hidden" name="pitch_deck" id="pitch_deck_field" value="<?php echo esc_attr($pitch_deck); ?>">
                <button type="button" class="pdp-upload-btn" id="upload_pitch_btn">📄 رفع PDF</button>
                <div id="pitch_preview" class="pdp-file-preview" style="<?php echo $pitch_deck ? '' : 'display:none;'; ?>">
                    <?php if ($pitch_deck): ?><span><?php echo basename(get_attached_file($pitch_deck)); ?></span><button type="button" onclick="document.getElementById('pitch_deck_field').value='';this.parentElement.style.display='none';" style="margin-right:10px;color:red;border:none;background:none;cursor:pointer;">✕</button><?php endif; ?>
                </div>
            </div>
            <div class="pdp-field full">
                <label>معرض صور المشروع / Project Gallery</label>
                <input type="hidden" name="project_gallery" id="gallery_field" value="<?php echo esc_attr($project_gallery); ?>">
                <button type="button" class="pdp-upload-btn" id="upload_gallery_btn">🖼️ إضافة صور</button>
                <div id="gallery_preview" class="pdp-gallery-preview">
                    <?php if ($project_gallery) { $ids = array_filter(explode(',', $project_gallery)); foreach ($ids as $id) { $img = wp_get_attachment_image_url($id, 'thumbnail'); if ($img) echo '<div class="pdp-gallery-item" data-id="'.$id.'"><img src="'.esc_url($img).'"><button type="button" class="remove-img">✕</button></div>'; } } ?>
                </div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($){
        $('.pdp-meta-tab').on('click',function(){var tab=$(this).data('tab');$('.pdp-meta-tab').removeClass('active');$(this).addClass('active');$('.pdp-tab-content').removeClass('active');$('#tab-'+tab).addClass('active')});
        $('#is_featured_checkbox').on('change',function(){$(this).is(':checked')?$('#featured_order_container').addClass('show'):$('#featured_order_container').removeClass('show')});
        var pitchFrame,galleryFrame;
        $('#upload_pitch_btn').on('click',function(e){e.preventDefault();if(!pitchFrame){pitchFrame=wp.media({title:'اختر PDF',library:{type:'application/pdf'},multiple:false});pitchFrame.on('select',function(){var att=pitchFrame.state().get('selection').first().toJSON();$('#pitch_deck_field').val(att.id);$('#pitch_preview').html('<span>'+att.filename+'</span><button type="button" onclick="document.getElementById(\'pitch_deck_field\').value=\'\';this.parentElement.style.display=\'none\';" style="margin-right:10px;color:red;border:none;background:none;cursor:pointer;">✕</button>').show()})}pitchFrame.open()});
        $('#upload_gallery_btn').on('click',function(e){e.preventDefault();if(!galleryFrame){galleryFrame=wp.media({title:'اختر صور',library:{type:'image'},multiple:true});galleryFrame.on('select',function(){var atts=galleryFrame.state().get('selection').toJSON();var ids=$('#gallery_field').val()?$('#gallery_field').val().split(',').filter(Boolean):[];atts.forEach(function(att){if(ids.indexOf(att.id.toString())===-1){ids.push(att.id);var thumb=att.sizes&&att.sizes.thumbnail?att.sizes.thumbnail.url:att.url;$('#gallery_preview').append('<div class="pdp-gallery-item" data-id="'+att.id+'"><img src="'+thumb+'"><button type="button" class="remove-img">✕</button></div>')}});$('#gallery_field').val(ids.join(','))})}galleryFrame.open()});
        $(document).on('click','.remove-img',function(){var item=$(this).closest('.pdp-gallery-item');var id=item.data('id').toString();var ids=$('#gallery_field').val().split(',').filter(function(i){return i&&i!==id});$('#gallery_field').val(ids.join(','));item.remove()});
    });
    </script>
    <?php
}

// ========== 7. حفظ البيانات ==========
add_action('save_post_project', 'pdp_save_meta');
function pdp_save_meta($post_id) {
    if (!isset($_POST['pdp_meta_nonce']) || !wp_verify_nonce($_POST['pdp_meta_nonce'], 'pdp_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $fields = ['founder_name','company_name','whatsapp','website','email','pitch_deck','project_details','project_gallery'];
    foreach ($fields as $field) { if (isset($_POST[$field])) update_post_meta($post_id, $field, sanitize_text_field($_POST[$field])); }
    $en_fields = ['title_en','founder_name_en','company_name_en','project_details_en','excerpt_en'];
    foreach ($en_fields as $field) { if (isset($_POST[$field])) update_post_meta($post_id, $field, sanitize_text_field($_POST[$field])); }
    $is_featured = isset($_POST['is_featured']) ? '1' : '0';
    update_post_meta($post_id, 'is_featured', $is_featured);
    if (isset($_POST['featured_order'])) { $order = intval($_POST['featured_order']); if ($order < 1) $order = 10; update_post_meta($post_id, 'featured_order', $order); }
}
// ========== 8. دالة ترتيب القطاعات + دالة البحث المرن ==========
function pdp_get_ordered_sectors($hide_empty = false) {
    $cache_key = 'pdp_sectors_' . ($hide_empty ? '1' : '0');
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;
    $ordered_sectors = [
        'التكنولوجيا والبرمجيات','الصحة والتكنولوجيا الحيوية','التعليم والتدريب',
        'الطاقة والاستدامة','الخدمات المالية والتكنولوجيا المالية (FinTech)',
        'التجارة والتجزئة','الاستشارات والتطوير الإداري','السياحة والضيافة',
        'الخدمات اللوجستية والنقل','الترفيه والإعلام','الصناعة والتصنيع المتقدم',
        'العقار والتطوير العمراني','الأمن السيبراني وحلول البيانات',
        'الغذاء والمشروبات','أخرى'
    ];
    $all_terms = get_terms(['taxonomy' => 'sector', 'hide_empty' => $hide_empty]);
    if (is_wp_error($all_terms) || empty($all_terms)) return [];
    $terms_by_name = [];
    foreach ($all_terms as $term) $terms_by_name[$term->name] = $term;
    $sorted = [];
    foreach ($ordered_sectors as $name) {
        if (isset($terms_by_name[$name])) { $sorted[] = $terms_by_name[$name]; unset($terms_by_name[$name]); }
    }
    $other_term = null;
    foreach ($sorted as $key => $term) {
        if ($term->name === 'أخرى') { $other_term = $term; unset($sorted[$key]); break; }
    }
    foreach ($terms_by_name as $term) $sorted[] = $term;
    if ($other_term) $sorted[] = $other_term;
    $sorted = array_values($sorted);
    set_transient($cache_key, $sorted, DAY_IN_SECONDS);
    return $sorted;
}

// دالة البحث المرن عن القطاع/المرحلة - تحل مشكلة الأقواس في FinTech
function pdp_find_term_flexible($value, $taxonomy) {
    if (empty($value)) return null;
    
    // 1. بالـ term_id (الطريقة الجديدة)
    if (is_numeric($value)) {
        $term = get_term(intval($value), $taxonomy);
        if ($term && !is_wp_error($term)) return $term;
    }
    
    // 2. بالـ slug
    $term = get_term_by('slug', $value, $taxonomy);
    if ($term) return $term;
    
    // 3. بالـ slug بعد decode
    $decoded = urldecode($value);
    $term = get_term_by('slug', $decoded, $taxonomy);
    if ($term) return $term;
    
    // 4. بالاسم
    $term = get_term_by('name', $decoded, $taxonomy);
    if ($term) return $term;
    
    // 5. sanitize_title
    $sanitized = sanitize_title($decoded);
    $term = get_term_by('slug', $sanitized, $taxonomy);
    if ($term) return $term;
    
    // 6. بحث يدوي - آخر حل
    $all_terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    if (is_wp_error($all_terms)) return null;
    
    foreach ($all_terms as $t) {
        if ($t->slug === $value || $t->slug === $sanitized) return $t;
        $clean_slug = preg_replace('/[()]+/', '', $t->slug);
        $clean_value = preg_replace('/[()]+/', '', $value);
        if ($clean_slug === $clean_value) return $t;
        if ((string)$t->term_id === (string)$value) return $t;
    }
    
    return null;
}


// ========== 9. دالة الحصول على بيانات المشروع ==========
function pdp_get_project_data($pid, $lang = 'ar') {
    $data = [
        'title' => get_the_title($pid),
        'founder_name' => get_post_meta($pid, 'founder_name', true),
        'company_name' => get_post_meta($pid, 'company_name', true),
        'project_details' => get_post_meta($pid, 'project_details', true),
        'excerpt' => get_the_excerpt($pid),
        'whatsapp' => get_post_meta($pid, 'whatsapp', true),
        'website' => get_post_meta($pid, 'website', true),
        'email' => get_post_meta($pid, 'email', true),
        'pitch_deck' => get_post_meta($pid, 'pitch_deck', true),
        'project_gallery' => get_post_meta($pid, 'project_gallery', true),
        'is_featured' => get_post_meta($pid, 'is_featured', true),
    ];
    if ($lang === 'en') {
        $en_map = ['title_en'=>'title','founder_name_en'=>'founder_name','company_name_en'=>'company_name','project_details_en'=>'project_details','excerpt_en'=>'excerpt'];
        foreach ($en_map as $meta_key => $data_key) {
            $val = get_post_meta($pid, $meta_key, true);
            if (!empty($val)) $data[$data_key] = $val;
        }
    }
    $data['pitch_url'] = $data['pitch_deck'] ? wp_get_attachment_url($data['pitch_deck']) : '';
    return $data;
}

// ========== 10. Shortcode العربي ==========
add_shortcode('projects_directory', 'pdp_render_shortcode');
function pdp_render_shortcode($atts) { return pdp_render_projects_grid($atts, 'ar'); }

// ========== 11. Shortcode الإنجليزي ==========
add_shortcode('projects_directory_en', 'pdp_render_shortcode_en');
function pdp_render_shortcode_en($atts) { return pdp_render_projects_grid($atts, 'en'); }

// ========== 12. دالة عرض المشاريع الموحدة ==========
function pdp_render_projects_grid($atts, $lang = 'ar') {
    $atts = shortcode_atts(['posts_per_page' => 12, 'columns' => 3], $atts);
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    $sector = isset($_GET['sector']) ? sanitize_text_field(urldecode($_GET['sector'])) : '';
    $stage = isset($_GET['stage']) ? sanitize_text_field(urldecode($_GET['stage'])) : '';
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    $is_english = ($lang === 'en');
    $vibes_holding_url = $is_english ? 'https://vibesholding.com/en/' : 'https://vibesholding.com/';
    $investors_club_url = $is_english ? 'https://vcmem.com/en/investors-club/' : 'https://vcmem.com/investors-club/';
    $success_partners_url = $is_english ? 'https://pvspaces.com/en/sp-2/' : 'https://pvspaces.com/sp/';

    $logo_pb = 'http://vibesholding.com/wp-content/uploads/2026/02/pb-png.webp';
    $logo_investors = 'http://vibesholding.com/wp-content/uploads/2026/02/لوجو-نادي-المستثمرين-1.webp';
    $logo_sp = 'http://vibesholding.com/wp-content/uploads/2026/02/شركاء-النجاح-1.webp';

    $texts = $is_english ? [
        'hero_title'=>'Projects Bank','hero_desc'=>'Projects Bank is a strategic initiative by <a href="'.$vibes_holding_url.'" target="_blank">Vibes Holding</a>, a professional platform aimed at presenting hundreds of promising partnership opportunities to members of the <a href="'.$investors_club_url.'" target="_blank">Investors Club</a>.','hero_desc_full'=>' The bank draws its strength from the "<a href="'.$success_partners_url.'" target="_blank">Success Partners Program</a>"; where entrepreneurs and company founders submit their project data and available opportunities, then <a href="'.$vibes_holding_url.'" target="_blank">Vibes Holding</a> reviews and evaluates them carefully before approving them for display in the Projects Bank.','read_more'=>'Read More','read_less'=>'Show Less','projects_available'=>'Projects Available','investors_club'=>'Investors Club','free_now'=>'Free Now','join_now'=>'Join Now','success_partners'=>'Success Partners','add_project'=>'Add Your Project','register_project'=>'Register Project','browse_projects'=>'Browse Projects and Connect with Founders','sector'=>'Sector','stage'=>'Stage','sort'=>'Sort','all'=>'All','newest'=>'Newest','most_viewed'=>'Most Viewed','featured_project'=>'Featured','project_details'=>'View Details','pitch_deck'=>'Pitch Deck','contact_founder'=>'Contact Founder','no_projects'=>'No projects available','business_gateway'=>'Business & Opportunities Gateway','choose_path'=>'Choose the right path for you','investor_desc'=>'Exclusive investment opportunities for the elite and partnerships in quality projects','partner_desc'=>'Looking for a strategic partner? List your project in the Projects Bank','enter_club'=>'Enter Club','register_project_btn'=>'Register Project','download_pitch'=>'Download Pitch Deck','contact_modal_title'=>'Contact Founder','pitch_modal_title'=>'Download Pitch Deck','form_success'=>'Request Received Successfully!','form_success_msg'=>'Thank you for your interest. Your request will be reviewed and we will contact you as soon as possible.','close'=>'Close',
    ] : [
        'hero_title'=>'بنك المشاريع','hero_desc'=>'بنك المشاريع هو إحدى المبادرات الاستراتيجية <a href="'.$vibes_holding_url.'" target="_blank">لفايبز القابضة</a>، فهو منصة احترافية تهدف لعرض مئات فرص الشراكة الواعدة على أعضاء <a href="'.$investors_club_url.'" target="_blank">نادي المستثمرين</a> من رجال وسيدات الأعمال والصناديق الاستثمارية.','hero_desc_full'=>' يستمد البنك قوته من "<a href="'.$success_partners_url.'" target="_blank">برنامج شركاء النجاح</a>"؛ حيث يقوم رواد الأعمال ومؤسسو الشركات بتقديم بيانات مشاريعهم والفرص المتاحة لديهم، لتبدأ <a href="'.$vibes_holding_url.'" target="_blank">فايبز القابضة</a> بمراجعتها وتقييمها بدقة قبل اعتماد عرضها في بنك المشاريع.','read_more'=>'اقرأ المزيد','read_less'=>'إخفاء','projects_available'=>'مشروع متاح للشراكة','investors_club'=>'نادي المستثمرين','free_now'=>'بدون رسوم حالياً','join_now'=>'انضم الآن','success_partners'=>'شركاء النجاح','add_project'=>'أضف مشروعك','register_project'=>'سجّل مشروعك','browse_projects'=>'استعرض المشاريع وتواصل مع مؤسسيها','sector'=>'القطاع','stage'=>'المرحلة','sort'=>'ترتيب','all'=>'الكل','newest'=>'الأحدث','most_viewed'=>'الأكثر مشاهدة','featured_project'=>'مشروع مميز','project_details'=>'تفاصيل المشروع','pitch_deck'=>'Pitch Deck','contact_founder'=>'تواصل مع المؤسس','no_projects'=>'لا توجد مشاريع حالياً','business_gateway'=>'بوابة الأعمال والفرص','choose_path'=>'اختر المسار المناسب لتوجهاتك','investor_desc'=>'فرص استثمارية حصرية للنخبة وشراكات في مشاريع نوعية','partner_desc'=>'هل تبحث عن شريك استراتيجي؟ أدرج مشروعك في بنك المشاريع','enter_club'=>'دخول النادي','register_project_btn'=>'تسجيل مشروع','download_pitch'=>'تحميل Pitch Deck','contact_modal_title'=>'تواصل مع المؤسس','pitch_modal_title'=>'تحميل Pitch Deck','form_success'=>'تم استلام طلبك بنجاح!','form_success_msg'=>'شكراً لاهتمامك. سيتم مراجعة طلبك والتواصل معك في أقرب وقت ممكن.','close'=>'إغلاق',
    ];

    // ========== الإصلاح: بناء tax_query بالـ term_id ==========
    $tax_query = [];
    if ($sector) {
        $sector_term = pdp_find_term_flexible($sector, 'sector');
        if ($sector_term) {
            $tax_query[] = ['taxonomy'=>'sector','field'=>'term_id','terms'=>$sector_term->term_id];
        }
    }
    if ($stage) {
        $stage_term = pdp_find_term_flexible($stage, 'project_stage');
        if ($stage_term) {
            $tax_query[] = ['taxonomy'=>'project_stage','field'=>'term_id','terms'=>$stage_term->term_id];
        }
    }

    $featured_ids = [];
    $featured_query = null;
    if ($paged == 1) {
        $featured_args = ['post_type'=>'project','posts_per_page'=>6,'meta_key'=>'featured_order','orderby'=>'meta_value_num','order'=>'ASC','meta_query'=>[['key'=>'is_featured','value'=>'1','compare'=>'=']]];
        if ($tax_query) $featured_args['tax_query'] = $tax_query;
        $featured_query = new WP_Query($featured_args);
        $featured_ids = wp_list_pluck($featured_query->posts, 'ID');
    }
    $args = ['post_type'=>'project','posts_per_page'=>$atts['posts_per_page'],'paged'=>$paged];
    $all_featured_ids = pdp_get_featured_ids_cached();
    if (!empty($all_featured_ids)) $args['post__not_in'] = $all_featured_ids;
    if ($orderby === 'views') { $args['meta_key']='views_count'; $args['orderby']='meta_value_num'; $args['order']='DESC'; } else { $args['orderby']='date'; $args['order']='DESC'; }
    if ($tax_query) $args['tax_query'] = $tax_query;
    $query = new WP_Query($args);
    $total = wp_count_posts('project')->publish;
    $dir = $is_english ? 'ltr' : 'rtl';
    $text_align = $is_english ? 'left' : 'right';

    $other_lang_base = $is_english ? home_url('/pb/') : home_url('/pb-eng/');
    $lang_params = [];
    if ($paged > 1)  $lang_params['paged'] = $paged;
    if ($sector)     $lang_params['sector'] = $sector;
    if ($stage)      $lang_params['stage'] = $stage;
    if ($orderby && $orderby !== 'date') $lang_params['orderby'] = $orderby;
    $other_lang_url = !empty($lang_params) ? add_query_arg($lang_params, $other_lang_base) : $other_lang_base;

    $other_lang_text = $is_english ? '🇸🇦 العربية' : '🇬🇧 English';
    ob_start();
    ?>
    <style>
    :root{--pdp-primary:<?php echo $is_english?'#1e40af':'#1D2D51';?>;--pdp-primary-light:<?php echo $is_english?'#3b82f6':'#2a4a7f';?>;--pdp-primary-dark:<?php echo $is_english?'#1e3a8a':'#0f1829';?>;--pdp-gold:#d4a853;--pdp-gold-light:#f0d78c;--pdp-gold-dark:#b8923d;--pdp-bg:#f8fafc;--pdp-card-bg:#ffffff;--pdp-text:#1e293b;--pdp-text-light:#475569;--pdp-border:#e2e8f0;--pdp-radius:16px;--pdp-radius-sm:10px;--pdp-shadow:0 4px 20px rgba(0,0,0,.08);--pdp-shadow-lg:0 10px 40px rgba(0,0,0,.12);--pdp-transition:all .4s cubic-bezier(.4,0,.2,1)}
    .pdp-wrapper *{box-sizing:border-box;margin:0;padding:0}.pdp-wrapper{max-width:1400px;margin:0 auto;padding:15px;font-family:<?php echo $is_english?"'Inter','Segoe UI',-apple-system,sans-serif":"'Cairo','Segoe UI',Tahoma,sans-serif";?>;background:var(--pdp-bg);direction:<?php echo $dir;?>;text-align:<?php echo $text_align;?>;line-height:1.6;color:var(--pdp-text)}
    .pdp-lang-switch{display:flex;justify-content:center;margin-bottom:12px;padding-top:5px}.pdp-lang-switch a{display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:var(--pdp-card-bg);color:var(--pdp-primary);border:2px solid var(--pdp-primary);border-radius:50px;text-decoration:none;font-weight:700;font-size:.9rem;transition:var(--pdp-transition);box-shadow:var(--pdp-shadow)}.pdp-lang-switch a:hover{background:var(--pdp-primary);color:#fff!important;transform:translateY(-2px)}
    .pdp-hero-section{background:var(--pdp-card-bg);border-radius:var(--pdp-radius);box-shadow:var(--pdp-shadow-lg);overflow:hidden;margin-bottom:20px;border:1px solid var(--pdp-border)}.pdp-hero-video{position:relative;width:100%;padding-bottom:45%;height:0;overflow:hidden;background:linear-gradient(135deg,var(--pdp-primary-dark),var(--pdp-primary))}.pdp-hero-video iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:0}
    .pdp-hero-content{padding:30px;text-align:center;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%)}.pdp-hero-logo{width:160px;height:160px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;padding:10px}.pdp-hero-logo img{width:100%;height:100%;object-fit:contain;transition:transform .4s ease}.pdp-hero-logo:hover img{transform:scale(1.08)}
    .pdp-hero-text-wrapper{max-width:850px;margin:0 auto 25px}.pdp-hero-desc{font-size:1rem;line-height:1.9;color:var(--pdp-text-light);margin:0;text-align:center}.pdp-hero-desc a{color:var(--pdp-primary);font-weight:700;text-decoration:none;border-bottom:2px solid var(--pdp-gold);transition:var(--pdp-transition)}.pdp-hero-desc a:hover{color:var(--pdp-gold-dark)}.pdp-desc-full{display:none}.pdp-hero-desc.expanded .pdp-desc-full{display:inline}
    .pdp-read-more{display:inline-flex;align-items:center;gap:8px;margin-top:15px;padding:8px 20px;background:rgba(29,45,81,.06);border-radius:30px;color:var(--pdp-primary);font-size:.85rem;font-weight:700;cursor:pointer;transition:var(--pdp-transition);border:none}.pdp-read-more:hover{background:var(--pdp-gold);color:#fff!important}.pdp-read-more svg{transition:transform .3s ease}.pdp-read-more.active svg{transform:rotate(180deg)}
    .pdp-hero-stats{display:inline-flex;align-items:center;gap:12px;background:linear-gradient(135deg,var(--pdp-gold) 0%,var(--pdp-gold-light) 50%,var(--pdp-gold) 100%);background-size:200% 200%;animation:pdpGoldShine 4s ease infinite;padding:14px 30px;border-radius:50px;box-shadow:0 6px 25px rgba(212,168,83,.35)}@keyframes pdpGoldShine{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
    .pdp-hero-stats-icon{width:26px;height:26px;color:var(--pdp-primary-dark);opacity:.8}.pdp-hero-stats-num{font-size:1.8rem;font-weight:900;color:var(--pdp-primary-dark);line-height:1}.pdp-hero-stats-label{font-size:.85rem;font-weight:700;color:var(--pdp-primary-dark);opacity:.9}
    .pdp-cta-futuristic{display:flex;align-items:center;justify-content:center;gap:30px;padding:18px 25px;margin-bottom:15px;background:linear-gradient(135deg,#fff 0%,#f1f5f9 100%);border-radius:var(--pdp-radius);border:1px solid var(--pdp-border);box-shadow:var(--pdp-shadow);position:relative}.pdp-cta-futuristic::after{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:1px;height:40px;background:linear-gradient(180deg,transparent,var(--pdp-border),transparent)}
    .pdp-cta-item-future{display:flex;align-items:center;gap:14px;padding:8px 0}.pdp-cta-logo-wrap{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;transition:var(--pdp-transition)}.pdp-cta-logo-wrap img{width:100%;height:100%;object-fit:contain}.pdp-cta-logo-wrap:hover{transform:scale(1.1)}.pdp-cta-content{display:flex;flex-direction:column;gap:2px}.pdp-cta-title{font-size:.95rem;font-weight:800;color:var(--pdp-primary)}.pdp-cta-subtitle{font-size:.75rem;color:var(--pdp-text-light);font-weight:500}
    .pdp-cta-btn-future{padding:10px 22px;border-radius:30px;font-size:.82rem;font-weight:700;text-decoration:none;transition:var(--pdp-transition);white-space:nowrap}.pdp-cta-btn-dark{background:var(--pdp-primary);color:#fff!important;border:none}.pdp-cta-btn-dark:hover{background:var(--pdp-primary-light);transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,45,81,.25);color:#fff!important}.pdp-cta-btn-outline{background:transparent;color:var(--pdp-primary)!important;border:2px solid var(--pdp-primary)}.pdp-cta-btn-outline:hover{background:var(--pdp-primary);color:#fff!important;transform:translateY(-2px)}
    .pdp-scroll-section{text-align:center;padding:20px;margin-bottom:15px;background:linear-gradient(135deg,rgba(212,168,83,.06) 0%,rgba(29,45,81,.04) 100%);border-radius:var(--pdp-radius);border:1px solid rgba(212,168,83,.15)}.pdp-scroll-btn{display:inline-flex;align-items:center;gap:12px;padding:14px 40px;background:linear-gradient(135deg,var(--pdp-gold),var(--pdp-gold-light));color:var(--pdp-primary-dark)!important;border:none;border-radius:50px;font-family:inherit;font-size:1rem;font-weight:800;cursor:pointer;transition:var(--pdp-transition);box-shadow:0 6px 25px rgba(212,168,83,.4)}.pdp-scroll-btn:hover{transform:translateY(-3px) scale(1.02);box-shadow:0 10px 35px rgba(212,168,83,.5);color:var(--pdp-primary-dark)!important}.pdp-scroll-arrow{animation:pdpBounceDown 1.8s ease infinite}@keyframes pdpBounceDown{0%,100%{transform:translateY(0)}50%{transform:translateY(6px)}}
    .pdp-filters{display:flex;align-items:center;justify-content:center;gap:15px;margin-bottom:20px;padding:16px 20px;background:var(--pdp-card-bg);border-radius:var(--pdp-radius);box-shadow:var(--pdp-shadow);flex-wrap:wrap;border:1px solid var(--pdp-border)}.pdp-filter-group{display:flex;align-items:center;gap:8px}.pdp-filter-group label{font-weight:700;color:var(--pdp-primary);font-size:.85rem;white-space:nowrap}.pdp-filter-select select{padding:10px 38px 10px 14px;border:2px solid var(--pdp-border);border-radius:8px;min-width:160px;font-size:.85rem;font-weight:600;background:var(--pdp-card-bg);color:var(--pdp-text);cursor:pointer;transition:var(--pdp-transition);-webkit-appearance:none;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:<?php echo $is_english?'right 12px center':'left 12px center';?>}.pdp-filter-select select:hover,.pdp-filter-select select:focus{border-color:var(--pdp-gold);box-shadow:0 0 0 3px rgba(212,168,83,.12);outline:none}.pdp-filter-divider{width:1px;height:30px;background:var(--pdp-border);border-radius:1px}
    .pdp-projects-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
    .pdp-project-card{background:var(--pdp-card-bg);border-radius:var(--pdp-radius);overflow:hidden;box-shadow:var(--pdp-shadow);transition:var(--pdp-transition);border:1px solid var(--pdp-border);position:relative;display:flex;flex-direction:column;opacity:0;transform:translateY(30px);animation:pdpCardIn .6s cubic-bezier(.4,0,.2,1) forwards}.pdp-project-card:nth-child(1){animation-delay:.05s}.pdp-project-card:nth-child(2){animation-delay:.1s}.pdp-project-card:nth-child(3){animation-delay:.15s}.pdp-project-card:nth-child(4){animation-delay:.2s}.pdp-project-card:nth-child(5){animation-delay:.25s}.pdp-project-card:nth-child(6){animation-delay:.3s}@keyframes pdpCardIn{to{opacity:1;transform:translateY(0)}}
    .pdp-project-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--pdp-gold),var(--pdp-gold-light),var(--pdp-gold));transform:scaleX(0);transition:transform .4s ease;z-index:10}.pdp-project-card:hover::before{transform:scaleX(1)}.pdp-project-card:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(0,0,0,.12);border-color:rgba(212,168,83,.3)}
    .pdp-project-card.pdp-featured{background:linear-gradient(145deg,#fffef7 0%,#fefcf0 100%);border:2px solid var(--pdp-gold);box-shadow:0 6px 25px rgba(212,168,83,.18)}.pdp-project-card.pdp-featured::before{transform:scaleX(1);background:linear-gradient(90deg,var(--pdp-gold-dark),var(--pdp-gold),var(--pdp-gold-light),var(--pdp-gold),var(--pdp-gold-dark));background-size:200% 100%;animation:pdpFeaturedBar 3s linear infinite;height:4px}@keyframes pdpFeaturedBar{0%{background-position:0% 0%}100%{background-position:200% 0%}}.pdp-project-card.pdp-featured:hover{border-color:var(--pdp-gold-dark);box-shadow:0 25px 60px rgba(212,168,83,.3);transform:translateY(-10px)}
    .pdp-vibes-badge{position:absolute;top:14px;left:14px;width:60px;height:60px;z-index:25;animation:pdpBadgeFloat 6s ease-in-out infinite;filter:drop-shadow(0 6px 18px rgba(29,45,81,.5))}@keyframes pdpBadgeFloat{0%,100%{transform:translateY(0) scale(1)}20%{transform:translateY(-6px) scale(1.05)}40%{transform:translateY(-2px) scale(1.02)}60%{transform:translateY(-8px) scale(1.06)}80%{transform:translateY(-3px) scale(1.01)}}.pdp-vibes-badge-inner{width:100%;height:100%;border-radius:50%;background:linear-gradient(145deg,#e8f6fc 0%,#d0eefb 30%,#b8e4f8 55%,#d6f0fd 80%,#eaf8ff 100%);display:flex;align-items:center;justify-content:center;border:2.5px solid #d4a853;box-shadow:0 0 0 3px rgba(212,168,83,.3),0 0 15px rgba(212,168,83,.25),0 0 30px rgba(29,45,81,.4),inset 0 1px 1px rgba(255,255,255,.15);position:relative;overflow:visible}.pdp-vibes-badge-inner::before{content:'';position:absolute;top:-4px;left:-4px;right:-4px;bottom:-4px;border-radius:50%;background:conic-gradient(from 0deg,transparent 0%,rgba(212,168,83,.6) 25%,transparent 50%,rgba(212,168,83,.6) 75%,transparent 100%);animation:pdpBadgeRingSpin 4s linear infinite;z-index:-1}.pdp-vibes-badge-inner::after{content:'';position:absolute;top:-2px;left:-2px;right:-2px;bottom:-2px;border-radius:50%;background:transparent;border:1px solid rgba(212,168,83,.15);animation:pdpBadgePulse 2.5s ease-in-out infinite}@keyframes pdpBadgeRingSpin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@keyframes pdpBadgePulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.18);opacity:0}}.pdp-vibes-badge-inner img{width:34px;height:34px;object-fit:contain;border-radius:50%;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3));transition:transform .4s ease;position:relative;z-index:2}.pdp-vibes-badge:hover .pdp-vibes-badge-inner img{transform:scale(1.15) rotate(5deg)}
    .pdp-featured-ribbon{position:absolute;top:15px;right:-30px;background:linear-gradient(135deg,var(--pdp-gold-dark),var(--pdp-gold));color:var(--pdp-primary-dark);padding:6px 40px;font-size:.68rem;font-weight:800;transform:rotate(45deg);z-index:20;box-shadow:0 3px 12px rgba(212,168,83,.4);letter-spacing:.5px;text-transform:uppercase}
    .pdp-card-image{position:relative;width:100%;padding-bottom:75%;overflow:hidden;background:linear-gradient(135deg,#f1f5f9,#e2e8f0)}.pdp-card-image img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:transform .5s ease}.pdp-project-card:hover .pdp-card-image img{transform:scale(1.05)}
    .pdp-card-body{padding:18px;flex:1;display:flex;flex-direction:column}.pdp-card-title{font-size:1.1rem;font-weight:800;color:var(--pdp-primary);margin-bottom:8px;line-height:1.4;transition:color .3s ease}.pdp-project-card:hover .pdp-card-title{color:var(--pdp-gold-dark)}.pdp-card-excerpt{font-size:.85rem;color:var(--pdp-text-light);line-height:1.6;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .pdp-card-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:15px}.pdp-tag{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:.72rem;font-weight:700;transition:var(--pdp-transition)}.pdp-tag-sector{background:rgba(30,64,175,.08);color:var(--pdp-primary)}.pdp-tag-sector::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--pdp-primary)}.pdp-tag-stage{background:rgba(212,168,83,.12);color:var(--pdp-gold-dark)}.pdp-tag-stage::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--pdp-gold)}
    .pdp-card-actions{display:flex;flex-direction:column;gap:8px;margin-top:auto}.pdp-action-btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none;cursor:pointer;border:none;transition:var(--pdp-transition);width:100%}.pdp-action-btn svg{width:16px;height:16px;flex-shrink:0}
    .pdp-btn-details{background:var(--pdp-primary);color:#fff!important}.pdp-btn-details:hover{background:var(--pdp-primary-light);transform:translateY(-2px);box-shadow:0 6px 20px rgba(29,45,81,.3);color:#fff!important}.pdp-btn-pitch{background:linear-gradient(135deg,var(--pdp-gold),var(--pdp-gold-light));color:var(--pdp-primary-dark)!important}.pdp-btn-pitch:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(212,168,83,.4);color:var(--pdp-primary-dark)!important}.pdp-btn-contact{background:var(--pdp-card-bg);border:2px solid var(--pdp-primary);color:var(--pdp-primary)!important}.pdp-btn-contact:hover{background:var(--pdp-primary);color:#fff!important;transform:translateY(-2px)}
    .pdp-pagination{display:flex;justify-content:center;gap:8px;margin-top:35px;flex-wrap:wrap}.pdp-pagination a,.pdp-pagination span{padding:10px 18px;border-radius:8px;background:var(--pdp-card-bg);color:var(--pdp-primary);text-decoration:none;font-weight:700;font-size:.9rem;transition:var(--pdp-transition);box-shadow:var(--pdp-shadow);border:1px solid var(--pdp-border)}.pdp-pagination a:hover{background:var(--pdp-gold);color:#fff!important;border-color:var(--pdp-gold)}.pdp-pagination .current{background:var(--pdp-primary);color:#fff!important;border-color:var(--pdp-primary)}
    .pdp-empty-state{text-align:center;padding:60px 25px;color:var(--pdp-text-light);background:var(--pdp-card-bg);border-radius:var(--pdp-radius);box-shadow:var(--pdp-shadow)}.pdp-empty-icon{font-size:3.5rem;margin-bottom:15px;animation:pdpFloat 3s ease-in-out infinite}@keyframes pdpFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}.pdp-empty-state h3{font-size:1.2rem;color:var(--pdp-primary);font-weight:700}
    .pdp-business-gateway{margin-top:50px;padding:45px 30px;background:linear-gradient(180deg,var(--pdp-primary-dark) 0%,#1e293b 100%);border-radius:24px;text-align:center;position:relative;overflow:hidden}.pdp-business-gateway::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle at 25% 25%,rgba(212,168,83,.12) 0%,transparent 50%),radial-gradient(circle at 75% 75%,rgba(59,130,246,.08) 0%,transparent 50%);pointer-events:none}
    .pdp-gateway-header{position:relative;z-index:1;margin-bottom:35px}.pdp-gateway-header h2{font-size:clamp(22px,4vw,34px);font-weight:900;color:#fff;margin-bottom:8px}.pdp-gateway-header .sub-head{font-size:.95rem;color:#94a3b8;font-weight:500}
    .pdp-gateway-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:25px;max-width:850px;margin:0 auto;position:relative;z-index:1}.pdp-gateway-card{background:rgba(30,41,59,.7);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:30px 25px;text-align:center;backdrop-filter:blur(12px);transition:var(--pdp-transition)}.pdp-gateway-card:hover{transform:translateY(-8px);border-color:rgba(255,255,255,.2)}.pdp-gateway-card.investor:hover{box-shadow:0 20px 50px rgba(212,168,83,.25)}.pdp-gateway-card.partner:hover{box-shadow:0 20px 50px rgba(59,130,246,.25)}
    .pdp-gateway-logo{width:75px;height:75px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;transition:transform .4s ease}.pdp-gateway-logo img{width:100%;height:100%;object-fit:contain}.pdp-gateway-card:hover .pdp-gateway-logo{transform:scale(1.12)}.pdp-gateway-card h3{font-size:1.2rem;font-weight:900;margin-bottom:12px}.pdp-gateway-card.investor h3{color:#fef3c7}.pdp-gateway-card.partner h3{color:#dbeafe}.pdp-gateway-card p{font-size:.88rem;line-height:1.7;color:#94a3b8;margin-bottom:22px}
    .pdp-gateway-btn{display:inline-block;padding:12px 32px;border-radius:50px;text-decoration:none;font-weight:800;font-size:.88rem;transition:var(--pdp-transition)}.pdp-gateway-btn-gold{background:linear-gradient(135deg,var(--pdp-gold),var(--pdp-gold-dark));color:var(--pdp-primary-dark)!important}.pdp-gateway-btn-gold:hover{transform:scale(1.06);box-shadow:0 10px 30px rgba(212,168,83,.45);color:var(--pdp-primary-dark)!important}.pdp-gateway-btn-blue{background:linear-gradient(135deg,#60a5fa,#3b82f6);color:#fff!important}.pdp-gateway-btn-blue:hover{transform:scale(1.06);box-shadow:0 10px 30px rgba(59,130,246,.45);color:#fff!important}
    .pdp-modal-bg{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,.9);backdrop-filter:blur(10px);z-index:999999;display:none;align-items:center;justify-content:center;padding:20px}.pdp-modal-bg.show{display:flex}.pdp-modal-container{background:var(--pdp-card-bg);border-radius:20px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;animation:pdpModalIn .4s ease;box-shadow:0 25px 80px rgba(0,0,0,.4)}@keyframes pdpModalIn{from{opacity:0;transform:scale(.9) translateY(40px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .pdp-modal-head{padding:18px 24px;border-bottom:1px solid var(--pdp-border);display:flex;justify-content:space-between;align-items:center;background:#fafafa}.pdp-modal-title{font-size:1.05rem;font-weight:800;color:var(--pdp-primary)}.pdp-modal-close-btn{width:36px;height:36px;border-radius:50%;border:none;background:var(--pdp-card-bg);cursor:pointer;font-size:1.3rem;display:flex;align-items:center;justify-content:center;transition:var(--pdp-transition);color:var(--pdp-text-light);box-shadow:0 2px 8px rgba(0,0,0,.08)}.pdp-modal-close-btn:hover{background:#ef4444;color:#fff!important;transform:rotate(90deg)}.pdp-modal-content{padding:24px;direction:<?php echo $dir;?>;text-align:<?php echo $text_align;?>}
    .pdp-pitch-success{display:none;text-align:center;padding:40px 20px}.pdp-pitch-success.show{display:block}.pdp-pitch-success-icon{width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;animation:pdpSuccessPop .5s ease}@keyframes pdpSuccessPop{0%{transform:scale(0)}50%{transform:scale(1.2)}100%{transform:scale(1)}}.pdp-pitch-success-icon svg{width:40px;height:40px;color:#fff}.pdp-pitch-success h3{font-size:1.3rem;color:var(--pdp-primary);margin-bottom:10px;font-weight:800}.pdp-pitch-success p{color:var(--pdp-text-light);font-size:.95rem;line-height:1.7;margin-bottom:20px}.pdp-pitch-success-btn{display:inline-block;padding:12px 30px;background:var(--pdp-primary);color:#fff!important;border-radius:8px;font-weight:700;cursor:pointer;border:none;transition:var(--pdp-transition)}.pdp-pitch-success-btn:hover{background:var(--pdp-primary-light);transform:translateY(-2px);color:#fff!important}
    @media(max-width:1024px){.pdp-projects-grid{grid-template-columns:repeat(2,1fr);gap:18px}.pdp-hero-content{padding:25px}.pdp-cta-futuristic{gap:25px;padding:15px 20px}}
    @media(max-width:768px){.pdp-wrapper{padding:10px}.pdp-lang-switch{margin-bottom:10px;padding-top:0}.pdp-lang-switch a{padding:8px 18px;font-size:.85rem}.pdp-hero-video{padding-bottom:56.25%;display:block!important;visibility:visible!important;opacity:1!important;height:auto!important;min-height:200px}.pdp-hero-video iframe{display:block!important;visibility:visible!important}.pdp-hero-content{padding:20px 15px}.pdp-hero-logo{width:130px;height:130px;margin-bottom:15px}.pdp-hero-desc{font-size:.92rem;line-height:1.8}.pdp-hero-stats{flex-direction:row;gap:10px;padding:12px 25px}.pdp-hero-stats-num{font-size:1.5rem}.pdp-hero-stats-label{font-size:.78rem}.pdp-cta-futuristic{flex-direction:column;gap:12px;padding:15px}.pdp-cta-futuristic::after{display:none}.pdp-cta-item-future{width:100%;justify-content:flex-start;background:rgba(248,250,252,.8);padding:14px 16px;border-radius:12px;border:1px solid var(--pdp-border)}.pdp-cta-logo-wrap{width:45px;height:45px}.pdp-cta-content{flex:1}.pdp-cta-title{font-size:.88rem}.pdp-cta-btn-future{padding:8px 18px;font-size:.78rem}.pdp-scroll-section{padding:15px}.pdp-scroll-btn{padding:12px 25px;font-size:.9rem;width:100%;justify-content:center}.pdp-filters{flex-direction:column;gap:12px;padding:15px}.pdp-filter-group{width:100%;justify-content:space-between}.pdp-filter-select{flex:1}.pdp-filter-select select{width:100%;min-width:unset;padding:10px 35px 10px 12px}.pdp-filter-divider{display:none}.pdp-projects-grid{grid-template-columns:1fr;gap:15px}.pdp-project-card{border-radius:12px}.pdp-card-image{padding-bottom:65%}.pdp-vibes-badge{width:48px;height:48px;top:10px;left:10px}.pdp-vibes-badge-inner img{width:30px;height:30px}.pdp-featured-ribbon{font-size:.6rem;padding:5px 35px;top:12px;right:-32px}.pdp-card-body{padding:15px}.pdp-card-title{font-size:1rem}.pdp-card-excerpt{font-size:.82rem}.pdp-tag{padding:4px 10px;font-size:.7rem}.pdp-action-btn{padding:9px 12px;font-size:.8rem}.pdp-business-gateway{padding:35px 18px;margin-top:35px;border-radius:18px}.pdp-gateway-header h2{font-size:1.4rem}.pdp-gateway-grid{grid-template-columns:1fr;gap:18px}.pdp-gateway-card{padding:25px 20px;border-radius:16px}.pdp-gateway-logo{width:65px;height:65px;margin-bottom:15px}.pdp-gateway-card h3{font-size:1.1rem}.pdp-gateway-card p{font-size:.85rem;margin-bottom:18px}.pdp-gateway-btn{padding:10px 28px;font-size:.85rem}.pdp-modal-container{max-width:95%;border-radius:16px}.pdp-modal-head{padding:15px 18px}.pdp-modal-title{font-size:.95rem}.pdp-modal-content{padding:18px 15px}.pdp-pagination{gap:6px;margin-top:28px}.pdp-pagination a,.pdp-pagination span{padding:8px 14px;font-size:.85rem}}
    @media(max-width:400px){.pdp-wrapper{padding:8px}.pdp-hero-content{padding:15px 12px}.pdp-hero-logo{width:110px;height:110px}.pdp-hero-desc{font-size:.88rem}.pdp-hero-stats{padding:10px 20px}.pdp-hero-stats-num{font-size:1.4rem}.pdp-cta-item-future{padding:12px 14px}.pdp-scroll-btn{padding:10px 20px;font-size:.85rem}.pdp-card-body{padding:12px}.pdp-card-title{font-size:.95rem}.pdp-action-btn{padding:8px 10px;font-size:.78rem}.pdp-action-btn svg{width:14px;height:14px}}
    </style>
    <div class="pdp-wrapper">
        <div class="pdp-lang-switch"><a href="<?php echo esc_url($other_lang_url); ?>"><?php echo $other_lang_text; ?></a></div>
        <div class="pdp-hero-section">
            <div class="pdp-hero-video"><iframe src="https://www.youtube.com/embed/xsoLckayQZA?rel=0&modestbranding=1&playsinline=1" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen loading="lazy" title="Projects Bank Video"></iframe></div>
            <div class="pdp-hero-content">
                <div class="pdp-hero-logo"><img src="<?php echo esc_url($logo_pb); ?>" alt="<?php echo esc_attr($texts['hero_title']); ?>"></div>
                <div class="pdp-hero-text-wrapper">
                    <p class="pdp-hero-desc" id="pdpDescText<?php echo $lang; ?>"><?php echo $texts['hero_desc']; ?><span class="pdp-desc-full"><?php echo $texts['hero_desc_full']; ?></span></p>
                    <button class="pdp-read-more" id="pdpReadMore<?php echo $lang; ?>" type="button"><?php echo $texts['read_more']; ?><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg></button>
                </div>
                <div class="pdp-hero-stats">
                    <svg class="pdp-hero-stats-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="pdp-hero-stats-num" id="pdpCounter<?php echo $lang; ?>">0</span>
                    <span class="pdp-hero-stats-label"><?php echo $texts['projects_available']; ?></span>
                </div>
            </div>
        </div>
        <div class="pdp-cta-futuristic">
            <div class="pdp-cta-item-future">
                <a href="<?php echo esc_url($investors_club_url); ?>" target="_blank" class="pdp-cta-logo-wrap"><img src="<?php echo esc_url($logo_investors); ?>" alt="<?php echo esc_attr($texts['investors_club']); ?>"></a>
                <div class="pdp-cta-content"><span class="pdp-cta-title"><?php echo $texts['investors_club']; ?></span><span class="pdp-cta-subtitle"><?php echo $texts['free_now']; ?></span></div>
                <a href="<?php echo esc_url($investors_club_url); ?>" target="_blank" class="pdp-cta-btn-future pdp-cta-btn-dark"><?php echo $texts['join_now']; ?></a>
            </div>
            <div class="pdp-cta-item-future">
                <a href="<?php echo esc_url($success_partners_url); ?>" target="_blank" class="pdp-cta-logo-wrap"><img src="<?php echo esc_url($logo_sp); ?>" alt="<?php echo esc_attr($texts['success_partners']); ?>"></a>
                <div class="pdp-cta-content"><span class="pdp-cta-title"><?php echo $texts['success_partners']; ?></span><span class="pdp-cta-subtitle"><?php echo $texts['add_project']; ?></span></div>
                <a href="<?php echo esc_url($success_partners_url); ?>" target="_blank" class="pdp-cta-btn-future pdp-cta-btn-outline"><?php echo $texts['register_project']; ?></a>
            </div>
        </div>
        <div class="pdp-scroll-section">
            <button class="pdp-scroll-btn" onclick="document.getElementById('pdpProjectsGrid<?php echo $lang; ?>').scrollIntoView({behavior:'smooth',block:'start'})"><?php echo $texts['browse_projects']; ?><svg class="pdp-scroll-arrow" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></button>
        </div>
        <div class="pdp-filters">
            <div class="pdp-filter-group"><label><?php echo $texts['sector']; ?>:</label><div class="pdp-filter-select"><select id="pdpSectorFilter<?php echo $lang; ?>"><option value=""><?php echo $texts['all']; ?></option><?php foreach (pdp_get_ordered_sectors(true) as $s): ?><option value="<?php echo esc_attr($s->term_id); ?>" <?php if ($sector && ($sector == $s->term_id || $sector == $s->slug)) echo 'selected'; ?>><?php echo esc_html(pdp_translate_term($s->name, 'sector', $lang)); ?></option><?php endforeach; ?></select></div></div>
            <div class="pdp-filter-divider"></div>
            <div class="pdp-filter-group"><label><?php echo $texts['stage']; ?>:</label><div class="pdp-filter-select"><select id="pdpStageFilter<?php echo $lang; ?>"><option value=""><?php echo $texts['all']; ?></option><?php $stages_list = get_terms(['taxonomy'=>'project_stage','hide_empty'=>true]); if (!is_wp_error($stages_list)) { foreach ($stages_list as $st): ?><option value="<?php echo esc_attr($st->term_id); ?>" <?php if ($stage && ($stage == $st->term_id || $stage == $st->slug)) echo 'selected'; ?>><?php echo esc_html(pdp_translate_term($st->name, 'stage', $lang)); ?></option><?php endforeach; } ?></select></div></div>
            <div class="pdp-filter-divider"></div>
            <div class="pdp-filter-group"><label><?php echo $texts['sort']; ?>:</label><div class="pdp-filter-select"><select id="pdpOrderFilter<?php echo $lang; ?>"><option value="date" <?php selected($orderby, 'date'); ?>><?php echo $texts['newest']; ?></option><option value="views" <?php selected($orderby, 'views'); ?>><?php echo $texts['most_viewed']; ?></option></select></div></div>
        </div>
        <?php
        $has_featured = ($paged == 1 && $featured_query && $featured_query->have_posts());
        $has_regular = $query->have_posts();
        if ($has_featured || $has_regular):
        ?>
        <div class="pdp-projects-grid" id="pdpProjectsGrid<?php echo $lang; ?>">
            <?php
            $render_card = function($pid, $pdata, $is_featured_card) use ($lang, $is_english, $texts) {
                $pitch_url = $pdata['pitch_url'];
                $project_sectors = get_the_terms($pid, 'sector');
                $project_stages = get_the_terms($pid, 'project_stage');
                $main_image = '';
                if (has_post_thumbnail()) {
                    $main_image = get_the_post_thumbnail_url($pid, 'medium_large');
                } elseif ($pdata['project_gallery']) {
                    $gallery_ids = array_filter(explode(',', $pdata['project_gallery']));
                    if (!empty($gallery_ids)) $main_image = wp_get_attachment_image_url($gallery_ids[0], 'medium_large');
                }
                if (!$main_image) $main_image = 'https://via.placeholder.com/600x450/1D2D51/C9A961?text='.urlencode($pdata['title']);
                $project_excerpt = !empty($pdata['excerpt']) ? $pdata['excerpt'] : $pdata['project_details'];
                $sector_display = '';
                $stage_display = '';
                if ($project_sectors && !is_wp_error($project_sectors)) $sector_display = pdp_translate_term($project_sectors[0]->name, 'sector', $lang);
                if ($project_stages && !is_wp_error($project_stages)) $stage_display = pdp_translate_term($project_stages[0]->name, 'stage', $lang);
                $gallery_attr = '';
                $gallery_urls = [];
                if (has_post_thumbnail()) $gallery_urls[] = get_the_post_thumbnail_url($pid, 'medium_large');
                if ($pdata['project_gallery']) {
                    foreach (array_filter(explode(',', $pdata['project_gallery'])) as $gid) {
                        $url = wp_get_attachment_image_url($gid, 'medium_large');
                        if ($url && !in_array($url, $gallery_urls)) $gallery_urls[] = $url;
                    }
                }
                if (count($gallery_urls) > 1) $gallery_attr = ' data-gallery="' . esc_attr(json_encode($gallery_urls)) . '"';

                // === بناء نص البحث (يشمل العربي + الإنجليزي + القطاع + المرحلة + المؤسس) ===
                $search_parts = [];
                $search_parts[] = get_the_title($pid);                       // العنوان العربي
                $search_parts[] = $pdata['title'];                            // العنوان المعروض (إنجليزي لو متاح)
                $search_parts[] = get_post_meta($pid, 'title_en', true);      // العنوان الإنجليزي صراحةً
                $search_parts[] = $pdata['founder_name'];
                $search_parts[] = get_post_meta($pid, 'company_name', true);
                $search_parts[] = $project_excerpt;
                $search_parts[] = get_post_meta($pid, 'excerpt_en', true);
                $search_parts[] = get_post_meta($pid, 'project_details_en', true);
                // كل أسماء القطاعات (عربي + إنجليزي)
                if ($project_sectors && !is_wp_error($project_sectors)) {
                    foreach ($project_sectors as $psec) {
                        $search_parts[] = $psec->name;
                        $search_parts[] = pdp_translate_term($psec->name, 'sector', 'en');
                    }
                }
                // كل أسماء المراحل (عربي + إنجليزي)
                if ($project_stages && !is_wp_error($project_stages)) {
                    foreach ($project_stages as $pst) {
                        $search_parts[] = $pst->name;
                        $search_parts[] = pdp_translate_term($pst->name, 'stage', 'en');
                    }
                }
                $search_text = strtolower(wp_strip_all_tags(implode(' ', array_filter($search_parts))));
                ?>
                <article class="pdp-project-card<?php echo $is_featured_card ? ' pdp-featured' : ''; ?>" data-pid="<?php echo (int)$pid; ?>" data-search-text="<?php echo esc_attr($search_text); ?>"<?php echo $gallery_attr; ?>>
                    <?php if ($is_featured_card): ?>
                        <div class="pdp-featured-ribbon"><?php echo $texts['featured_project']; ?></div>
<div class="pdp-vibes-badge"><div class="pdp-vibes-badge-inner"><img src="http://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp" alt="Vibes Holding"></div></div>
                    <?php endif; ?>
                    <div class="pdp-card-image"><img src="<?php echo esc_url($main_image); ?>" alt="<?php echo esc_attr($pdata['title']); ?>" loading="lazy"></div>
                    <div class="pdp-card-body">
                        <h3 class="pdp-card-title"><?php echo esc_html($pdata['title']); ?></h3>
                        <?php if ($project_excerpt): ?><p class="pdp-card-excerpt"><?php echo wp_trim_words(strip_tags($project_excerpt), 18, '...'); ?></p><?php endif; ?>
                        <div class="pdp-card-tags">
                            <?php if ($sector_display): ?><span class="pdp-tag pdp-tag-sector"><?php echo esc_html($sector_display); ?></span><?php endif; ?>
                            <?php if ($stage_display): ?><span class="pdp-tag pdp-tag-stage"><?php echo esc_html($stage_display); ?></span><?php endif; ?>
                        </div>
                        <div class="pdp-card-actions">
                            <a href="<?php the_permalink(); ?><?php echo $is_english ? '?lang=en' : ''; ?>" class="pdp-action-btn pdp-btn-details"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><?php echo $texts['project_details']; ?></a>
                            <?php if ($pitch_url): ?><button type="button" class="pdp-action-btn pdp-btn-pitch" data-project="<?php echo esc_attr($pdata['title']); ?>" data-pitch="<?php echo esc_url($pitch_url); ?>" data-founder="<?php echo esc_attr($pdata['founder_name']); ?>" data-whatsapp="<?php echo esc_attr($pdata['whatsapp']); ?>" onclick="pdpOpenPitchModal<?php echo $lang; ?>(this)"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><?php echo $texts['pitch_deck']; ?></button><?php endif; ?>
                            <button type="button" class="pdp-action-btn pdp-btn-contact" data-project="<?php echo esc_attr($pdata['title']); ?>" data-founder="<?php echo esc_attr($pdata['founder_name']); ?>" data-whatsapp="<?php echo esc_attr($pdata['whatsapp']); ?>" onclick="pdpOpenContactModal<?php echo $lang; ?>(this)"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><?php echo $texts['contact_founder']; ?></button>
                        </div>
                    </div>
                </article>
                <?php
            };
            if ($has_featured):
                while ($featured_query->have_posts()): $featured_query->the_post();
                    $pid = get_the_ID();
                    $render_card($pid, pdp_get_project_data($pid, $lang), true);
                endwhile; wp_reset_postdata();
            endif;
            while ($query->have_posts()): $query->the_post();
                $pid = get_the_ID();
                $render_card($pid, pdp_get_project_data($pid, $lang), false);
            endwhile; wp_reset_postdata();
            ?>
        </div>
        <div class="pdp-pagination"><?php echo paginate_links(['total'=>$query->max_num_pages,'current'=>$paged,'prev_text'=>$is_english?'← Prev':'السابق →','next_text'=>$is_english?'Next →':'← التالي','mid_size'=>2]); ?></div>
        <?php else: ?>
        <div class="pdp-empty-state"><div class="pdp-empty-icon">📂</div><h3><?php echo $texts['no_projects']; ?></h3></div>
        <?php endif; ?>
        <div class="pdp-business-gateway">
            <div class="pdp-gateway-header"><h2><?php echo $texts['business_gateway']; ?></h2><div class="sub-head"><?php echo $texts['choose_path']; ?></div></div>
            <div class="pdp-gateway-grid">
                <div class="pdp-gateway-card investor">
                    <a href="<?php echo esc_url($investors_club_url); ?>" target="_blank" class="pdp-gateway-logo"><img src="<?php echo esc_url($logo_investors); ?>" alt="<?php echo esc_attr($texts['investors_club']); ?>"></a>
                    <h3><?php echo $texts['investors_club']; ?></h3><p><?php echo $texts['investor_desc']; ?></p>
                    <a class="pdp-gateway-btn pdp-gateway-btn-gold" href="<?php echo esc_url($investors_club_url); ?>" target="_blank"><?php echo $texts['enter_club']; ?></a>
                </div>
                <div class="pdp-gateway-card partner">
                    <a href="<?php echo esc_url($success_partners_url); ?>" target="_blank" class="pdp-gateway-logo"><img src="<?php echo esc_url($logo_sp); ?>" alt="<?php echo esc_attr($texts['success_partners']); ?>"></a>
                    <h3><?php echo $texts['success_partners']; ?></h3><p><?php echo $texts['partner_desc']; ?></p>
                    <a class="pdp-gateway-btn pdp-gateway-btn-blue" href="<?php echo esc_url($success_partners_url); ?>" target="_blank"><?php echo $texts['register_project_btn']; ?></a>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals -->
    <div class="pdp-modal-bg" id="pdpPitchModal<?php echo $lang; ?>"><div class="pdp-modal-container"><div class="pdp-modal-head"><h3 class="pdp-modal-title"><?php echo $texts['pitch_modal_title']; ?> - <span id="pdpPitchProjectTitle<?php echo $lang; ?>"></span></h3><button type="button" class="pdp-modal-close-btn" onclick="pdpCloseModal<?php echo $lang; ?>('pdpPitchModal<?php echo $lang; ?>')">×</button></div><div class="pdp-modal-content"><div id="pdpPitchFormContainer<?php echo $lang; ?>"><?php echo do_shortcode($is_english ? '[fluentform id="32"]' : '[fluentform id="31"]'); ?></div><div class="pdp-pitch-success" id="pdpPitchSuccess<?php echo $lang; ?>"><div class="pdp-pitch-success-icon"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div><h3><?php echo $texts['form_success']; ?></h3><p><?php echo $texts['form_success_msg']; ?></p><button type="button" class="pdp-pitch-success-btn" onclick="pdpCloseModal<?php echo $lang; ?>('pdpPitchModal<?php echo $lang; ?>')"><?php echo $texts['close']; ?></button></div></div></div></div>
    <div class="pdp-modal-bg" id="pdpContactModal<?php echo $lang; ?>"><div class="pdp-modal-container"><div class="pdp-modal-head"><h3 class="pdp-modal-title"><?php echo $texts['contact_modal_title']; ?> - <span id="pdpContactProjectName<?php echo $lang; ?>"></span></h3><button type="button" class="pdp-modal-close-btn" onclick="pdpCloseModal<?php echo $lang; ?>('pdpContactModal<?php echo $lang; ?>')">×</button></div><div class="pdp-modal-content"><?php echo do_shortcode($is_english ? '[fluentform id="33"]' : '[fluentform id="30"]'); ?></div></div></div>
    <script>
    (function(){'use strict';var lang='<?php echo $lang;?>',total=<?php echo (int)$total;?>,texts={readMore:'<?php echo esc_js($texts['read_more']);?>',readLess:'<?php echo esc_js($texts['read_less']);?>'};
    function animateCounter(el,target,dur){if(!el||target<=0)return;var start=0,startTime=null;function easeOut(t){return 1-Math.pow(1-t,4)}function animate(ts){if(!startTime)startTime=ts;var prog=Math.min((ts-startTime)/dur,1);el.textContent=Math.floor(easeOut(prog)*target).toLocaleString();if(prog<1)requestAnimationFrame(animate);else el.textContent=target.toLocaleString()}requestAnimationFrame(animate)}
    var counter=document.getElementById('pdpCounter'+lang);if(counter&&total>0)setTimeout(function(){animateCounter(counter,total,1800)},400);
    var readMore=document.getElementById('pdpReadMore'+lang),descText=document.getElementById('pdpDescText'+lang);if(readMore&&descText){readMore.addEventListener('click',function(){descText.classList.toggle('expanded');this.classList.toggle('active');var svg='<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>';this.innerHTML=descText.classList.contains('expanded')?texts.readLess+' '+svg:texts.readMore+' '+svg})}
    ['pdpSectorFilter','pdpStageFilter','pdpOrderFilter'].forEach(function(id){var el=document.getElementById(id+lang);if(el){el.addEventListener('change',function(){var url=new URL(window.location.href);var sv=document.getElementById('pdpSectorFilter'+lang),stv=document.getElementById('pdpStageFilter'+lang),ov=document.getElementById('pdpOrderFilter'+lang);if(sv&&sv.value)url.searchParams.set('sector',sv.value);else url.searchParams.delete('sector');if(stv&&stv.value)url.searchParams.set('stage',stv.value);else url.searchParams.delete('stage');if(ov)url.searchParams.set('orderby',ov.value);url.searchParams.delete('paged');window.location.href=url.toString()})}});
    document.querySelectorAll('.pdp-modal-bg').forEach(function(m){m.addEventListener('click',function(e){if(e.target===this){window['pdpCloseModal'+lang](this.id)}})});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){document.querySelectorAll('.pdp-modal-bg.show').forEach(function(m){m.classList.remove('show');document.body.style.overflow=''})}});
    if(typeof jQuery!=='undefined'){jQuery(document).on('fluentform_submission_success',function(e,r,form){var pm=document.getElementById('pdpPitchModal'+lang);if(pm&&jQuery(form).closest(pm).length>0){var fc=document.getElementById('pdpPitchFormContainer'+lang),sm=document.getElementById('pdpPitchSuccess'+lang);if(fc)fc.style.display='none';if(sm)sm.classList.add('show')}})}})();
    function pdpFillHiddenField(mid,fnames,val){var m=document.getElementById(mid);if(!m)return false;if(typeof fnames==='string')fnames=[fnames];for(var i=0;i<fnames.length;i++){var f=m.querySelector('input[name="'+fnames[i]+'"]');if(f){f.value=val||'';return true}}return false}
    function pdpOpenPitchModal<?php echo $lang;?>(btn){var lang='<?php echo $lang;?>',m=document.getElementById('pdpPitchModal'+lang),ts=document.getElementById('pdpPitchProjectTitle'+lang),fc=document.getElementById('pdpPitchFormContainer'+lang),sm=document.getElementById('pdpPitchSuccess'+lang);if(ts)ts.textContent=btn.dataset.project||'';pdpFillHiddenField('pdpPitchModal'+lang,['project_name','project-name','names_1','hidden_field_1'],btn.dataset.project);pdpFillHiddenField('pdpPitchModal'+lang,['pitch_url','pitch-url','names_2','hidden_field_2'],btn.dataset.pitch);pdpFillHiddenField('pdpPitchModal'+lang,['founder_name','founder-name','names_3','hidden_field_3'],btn.dataset.founder);pdpFillHiddenField('pdpPitchModal'+lang,['founder_whatsapp','whatsapp','names_4','hidden_field_4'],btn.dataset.whatsapp);if(fc)fc.style.display='block';if(sm)sm.classList.remove('show');if(m){m.classList.add('show');document.body.style.overflow='hidden'}}
    function pdpOpenContactModal<?php echo $lang;?>(btn){var lang='<?php echo $lang;?>',m=document.getElementById('pdpContactModal'+lang),ns=document.getElementById('pdpContactProjectName'+lang);if(ns)ns.textContent=btn.dataset.project||'';pdpFillHiddenField('pdpContactModal'+lang,['project_name','project-name','names_1','hidden_field_1'],btn.dataset.project);pdpFillHiddenField('pdpContactModal'+lang,['founder_name','founder-name','names_2','hidden_field_2'],btn.dataset.founder);pdpFillHiddenField('pdpContactModal'+lang,['founder_whatsapp','whatsapp','names_3','hidden_field_3'],btn.dataset.whatsapp);if(m){m.classList.add('show');document.body.style.overflow='hidden'}}
    function pdpCloseModal<?php echo $lang;?>(id){var lang='<?php echo $lang;?>',m=document.getElementById(id);if(m){m.classList.remove('show');document.body.style.overflow='';if(id==='pdpPitchModal'+lang){setTimeout(function(){var fc=document.getElementById('pdpPitchFormContainer'+lang),sm=document.getElementById('pdpPitchSuccess'+lang);if(fc)fc.style.display='block';if(sm)sm.classList.remove('show')},300)}}}
    </script>
    <?php
    return ob_get_clean();
}


// ========== 13. زيادة المشاهدات AJAX ==========
add_action('wp_ajax_pdp_boost_views', 'pdp_ajax_boost_views');
add_action('wp_ajax_nopriv_pdp_boost_views', 'pdp_ajax_boost_views');
function pdp_ajax_boost_views() {
    $post_id = intval($_POST['post_id']);
    if (!$post_id || get_post_type($post_id) !== 'project') wp_send_json_error();
    $current_views = (int)get_post_meta($post_id, 'views_count', true);
    update_post_meta($post_id, 'views_count', $current_views + rand(3, 8));
    wp_send_json_success(['views' => $current_views]);
}

// ========== 14. Cron Job لزيادة المشاهدات ==========
register_activation_hook(__FILE__, 'pdp_schedule_views_boost');
function pdp_schedule_views_boost() {
    if (!wp_next_scheduled('pdp_daily_views_boost')) wp_schedule_event(time(), 'daily', 'pdp_daily_views_boost');
}
register_deactivation_hook(__FILE__, 'pdp_unschedule_views_boost');
function pdp_unschedule_views_boost() { wp_clear_scheduled_hook('pdp_daily_views_boost'); }

add_action('pdp_daily_views_boost', 'pdp_boost_all_projects_views');
function pdp_boost_all_projects_views() {
    global $wpdb;
    $wpdb->query("UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID SET pm.meta_value = CAST(pm.meta_value AS UNSIGNED) + FLOOR(5 + RAND() * 21) WHERE pm.meta_key = 'views_count' AND p.post_type = 'project' AND p.post_status = 'publish' AND p.ID NOT IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'is_featured' AND meta_value = '1')");
    $wpdb->query("UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id SET pm.meta_value = CAST(pm.meta_value AS UNSIGNED) + FLOOR(15 + RAND() * 36) WHERE pm.meta_key = 'views_count' AND p.post_type = 'project' AND p.post_status = 'publish' AND pm2.meta_key = 'is_featured' AND pm2.meta_value = '1'");
    delete_transient('pdp_total_views');
}

// ========== 15. زيادة المشاهدات عند زيارة صفحة المشروع ==========
add_action('template_redirect', 'pdp_track_project_view');
function pdp_track_project_view() {
    if (!is_singular('project')) return;
    global $post;
    $pid = $post->ID;
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $bots = ['bot','crawl','spider','slurp','mediapartners'];
    foreach ($bots as $bot) { if (stripos($user_agent, $bot) !== false) return; }
    $session_key = 'pdp_viewed_' . $pid;
    if (isset($_COOKIE[$session_key])) return;
    $current_views = (int)get_post_meta($pid, 'views_count', true);
    update_post_meta($pid, 'views_count', $current_views + rand(2, 6));
    setcookie($session_key, '1', time() + 3600, '/');
}

// ========== 16. قالب صفحة المشروع المفرد ==========
add_filter('single_template', 'pdp_single_template');
function pdp_single_template($template) {
    if (is_singular('project')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'single-project.php';
        if (file_exists($plugin_template)) return $plugin_template;
    }
    return $template;
}

add_action('template_redirect', 'pdp_auto_single_template');
function pdp_auto_single_template() {
    if (!is_singular('project')) return;
    add_filter('the_content', 'pdp_single_project_content', 20);
}

function pdp_single_project_content($content) {
    if (!is_singular('project')) return $content;
    global $post;
    $pid = $post->ID;
    $lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
    $is_english = ($lang === 'en');
    $pdata = pdp_get_project_data($pid, $lang);
    $sectors = get_the_terms($pid, 'sector');
    $stages = get_the_terms($pid, 'project_stage');
    $views = (int)get_post_meta($pid, 'views_count', true);
    $sector_display = '';
    $stage_display = '';
    if ($sectors && !is_wp_error($sectors)) $sector_display = pdp_translate_term($sectors[0]->name, 'sector', $lang);
    if ($stages && !is_wp_error($stages)) $stage_display = pdp_translate_term($stages[0]->name, 'stage', $lang);
    $vibes_holding_url = $is_english ? 'https://vibesholding.com/en/' : 'https://vibesholding.com/';
    $investors_club_url = $is_english ? 'https://vcmem.com/en/investors-club/' : 'https://vcmem.com/investors-club/';
    $success_partners_url = $is_english ? 'https://pvspaces.com/en/sp-2/' : 'https://pvspaces.com/sp/';
    $texts = $is_english ? [
        'featured_badge'=>'Vibes Holding Featured','founder'=>'Founder','company'=>'Company','sector'=>'Sector','stage'=>'Stage','visit_website'=>'Visit Website','views'=>'Views','download_pitch'=>'Download Pitch Deck','contact_founder'=>'Contact Founder','back'=>'Back to Projects','form_success'=>'Request Received!','form_success_msg'=>'Thank you. We will contact you soon.','close'=>'Close','project_details'=>'Project Details','project_gallery'=>'Gallery','share_project'=>'Share','copy_link'=>'Copy Link','link_copied'=>'Copied!',
    ] : [
        'featured_badge'=>'مشروع مميز من فايبز','founder'=>'المؤسس','company'=>'الشركة','sector'=>'القطاع','stage'=>'المرحلة','visit_website'=>'الموقع','views'=>'مشاهدة','download_pitch'=>'تحميل Pitch Deck','contact_founder'=>'تواصل مع المؤسس','back'=>'العودة للمشاريع','form_success'=>'تم استلام طلبك!','form_success_msg'=>'شكراً لك. سنتواصل معك قريباً.','close'=>'إغلاق','project_details'=>'تفاصيل المشروع','project_gallery'=>'معرض الصور','share_project'=>'مشاركة','copy_link'=>'نسخ الرابط','link_copied'=>'تم النسخ!',
    ];
    $dir = $is_english ? 'ltr' : 'rtl';
    $text_align = $is_english ? 'left' : 'right';
    $page_url = $is_english ? home_url('/pb-eng/') : home_url('/pb/');
    $other_lang_url = get_permalink($pid) . ($is_english ? '' : '?lang=en');
    $other_lang_text = $is_english ? '🇸🇦 العربية' : '🇬🇧 English';
    $current_url = get_permalink($pid) . ($is_english ? '?lang=en' : '');
    $gallery_images = [];
    if (has_post_thumbnail($pid)) $gallery_images[] = get_the_post_thumbnail_url($pid, 'large');
    if ($pdata['project_gallery']) {
        $gallery_ids = array_filter(explode(',', $pdata['project_gallery']));
        foreach ($gallery_ids as $gid) { $img_url = wp_get_attachment_image_url($gid, 'large'); if ($img_url) $gallery_images[] = $img_url; }
    }
    if (empty($gallery_images)) $gallery_images[] = 'https://via.placeholder.com/800x500/1D2D51/C9A961?text='.urlencode($pdata['title']);
    ob_start();
    ?>
    <style>
    :root{--pds-primary:<?php echo $is_english?'#1e40af':'#1D2D51';?>;--pds-primary-light:<?php echo $is_english?'#3b82f6':'#2a4a7f';?>;--pds-gold:#d4a853;--pds-gold-light:#f0d78c;--pds-gold-dark:#b8923d;--pds-bg:#f8fafc;--pds-card:#ffffff;--pds-text:#1e293b;--pds-text-light:#64748b;--pds-border:#e2e8f0;--pds-radius:14px;--pds-shadow:0 4px 20px rgba(0,0,0,.08)}
    .pds-wrapper *{box-sizing:border-box;margin:0;padding:0}.pds-wrapper{max-width:900px;margin:0 auto;padding:20px 15px;font-family:<?php echo $is_english?"'Inter',sans-serif":"'Cairo',sans-serif";?>;background:var(--pds-bg);direction:<?php echo $dir;?>;text-align:<?php echo $text_align;?>;color:var(--pds-text);line-height:1.7}
    .pds-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
    .pds-back-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--pds-card);color:var(--pds-primary)!important;border:2px solid var(--pds-border);border-radius:50px;text-decoration:none;font-weight:700;font-size:.85rem;transition:all .3s}.pds-back-btn:hover{background:var(--pds-primary);color:#fff!important;border-color:var(--pds-primary)}.pds-back-btn svg{width:16px;height:16px}
    .pds-lang-btn{padding:10px 20px;background:var(--pds-card);color:var(--pds-primary)!important;border:2px solid var(--pds-primary);border-radius:50px;text-decoration:none;font-weight:700;font-size:.85rem;transition:all .3s}.pds-lang-btn:hover{background:var(--pds-primary);color:#fff!important}
    .pds-featured-badge{display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,rgba(212,168,83,.12),rgba(240,215,140,.08));border:2px solid var(--pds-gold);color:var(--pds-primary);padding:10px 20px;border-radius:50px;font-weight:800;font-size:.85rem;margin-bottom:18px;box-shadow:0 4px 15px rgba(212,168,83,.25)}.pds-featured-badge img{width:28px;height:28px;border-radius:50%}
    .pds-title{font-size:clamp(1.5rem,4vw,2.2rem);font-weight:900;color:var(--pds-primary);margin-bottom:15px;line-height:1.3}
    .pds-tags{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}.pds-tag{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:700}.pds-tag::before{content:'';width:6px;height:6px;border-radius:50%}.pds-tag-sector{background:rgba(30,64,175,.08);color:var(--pds-primary)}.pds-tag-sector::before{background:var(--pds-primary)}.pds-tag-stage{background:rgba(212,168,83,.12);color:var(--pds-gold-dark)}.pds-tag-stage::before{background:var(--pds-gold)}
    .pds-meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;background:var(--pds-card);padding:22px;border-radius:var(--pds-radius);margin-bottom:25px;box-shadow:var(--pds-shadow);border:1px solid var(--pds-border)}.pds-meta-item{display:flex;align-items:center;gap:12px;padding:8px 0}.pds-meta-icon{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,rgba(212,168,83,.12),rgba(212,168,83,.05));display:flex;align-items:center;justify-content:center;flex-shrink:0}.pds-meta-icon svg{width:20px;height:20px;color:var(--pds-gold-dark)}.pds-meta-content{display:flex;flex-direction:column;gap:2px}.pds-meta-label{font-size:.75rem;color:var(--pds-text-light);font-weight:600}.pds-meta-value{font-size:.9rem;color:var(--pds-primary);font-weight:700}.pds-meta-value a{color:var(--pds-primary);text-decoration:none;border-bottom:1px solid var(--pds-gold)}.pds-meta-value a:hover{color:var(--pds-gold-dark)}
    .pds-gallery{margin-bottom:25px}.pds-section-title{font-size:1.15rem;font-weight:800;color:var(--pds-primary);margin-bottom:15px;display:flex;align-items:center;gap:10px}.pds-section-title::before{content:'';width:4px;height:22px;background:linear-gradient(180deg,var(--pds-gold),var(--pds-gold-light));border-radius:2px}
    .pds-gallery-main{position:relative;width:100%;padding-bottom:55%;border-radius:var(--pds-radius);overflow:hidden;background:var(--pds-card);box-shadow:var(--pds-shadow);margin-bottom:12px}.pds-gallery-main-img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:opacity .4s ease;cursor:zoom-in}
    .pds-gallery-nav{position:absolute;top:50%;transform:translateY(-50%);width:100%;display:flex;justify-content:space-between;padding:0 12px;pointer-events:none;z-index:5}.pds-gallery-nav-btn{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.9);color:var(--pds-primary);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;pointer-events:all;transition:all .3s;font-size:16px;font-weight:bold;box-shadow:0 3px 12px rgba(0,0,0,.15)}.pds-gallery-nav-btn:hover{background:var(--pds-gold);color:#fff!important;transform:scale(1.1)}
    .pds-gallery-counter{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);color:#fff;padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600}
    .pds-gallery-thumbs{display:flex;gap:10px;overflow-x:auto;padding:5px}.pds-gallery-thumbs::-webkit-scrollbar{height:5px}.pds-gallery-thumbs::-webkit-scrollbar-track{background:var(--pds-border);border-radius:3px}.pds-gallery-thumbs::-webkit-scrollbar-thumb{background:var(--pds-gold);border-radius:3px}
    .pds-thumb{width:70px;height:55px;border-radius:8px;overflow:hidden;cursor:pointer;flex-shrink:0;border:2px solid transparent;transition:all .3s;opacity:.6}.pds-thumb:hover{opacity:1}.pds-thumb.active{border-color:var(--pds-gold);opacity:1;box-shadow:0 3px 12px rgba(212,168,83,.35)}.pds-thumb img{width:100%;height:100%;object-fit:cover}
    .pds-details{background:var(--pds-card);border-radius:var(--pds-radius);padding:25px;margin-bottom:25px;box-shadow:var(--pds-shadow);border:1px solid var(--pds-border)}.pds-details-content{font-size:1rem;line-height:1.9;color:var(--pds-text)}.pds-details-content p{margin-bottom:15px}.pds-details-content p:last-child{margin-bottom:0}
    .pds-actions{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:25px}.pds-action-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 28px;border-radius:10px;font-weight:700;font-size:.95rem;text-decoration:none;cursor:pointer;border:none;transition:all .3s;flex:1;min-width:180px}.pds-action-btn svg{width:20px;height:20px}
    .pds-btn-pitch{background:linear-gradient(135deg,var(--pds-gold),var(--pds-gold-light));color:var(--pds-primary)!important}.pds-btn-pitch:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(212,168,83,.4);color:var(--pds-primary)!important}
    .pds-btn-contact{background:var(--pds-primary);color:#fff!important}.pds-btn-contact:hover{background:var(--pds-primary-light);transform:translateY(-3px);box-shadow:0 10px 30px rgba(29,45,81,.3);color:#fff!important}
    .pds-share{background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:var(--pds-radius);padding:20px;border:1px solid var(--pds-border)}.pds-share-title{font-size:1rem;font-weight:700;color:var(--pds-primary);margin-bottom:12px;display:flex;align-items:center;gap:8px}.pds-share-title svg{width:18px;height:18px;color:var(--pds-gold)}
    .pds-share-btns{display:flex;gap:10px;flex-wrap:wrap}.pds-share-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:8px;font-weight:600;font-size:.82rem;text-decoration:none;cursor:pointer;border:none;transition:all .3s}.pds-share-btn svg{width:18px;height:18px}
    .pds-share-btn-copy{background:var(--pds-card);color:var(--pds-primary)!important;border:2px solid var(--pds-border)}.pds-share-btn-copy:hover{background:var(--pds-primary);color:#fff!important;border-color:var(--pds-primary)}.pds-share-btn-copy.copied{background:#10b981;color:#fff!important;border-color:#10b981}
    .pds-share-btn-whatsapp{background:#25D366;color:#fff!important}.pds-share-btn-whatsapp:hover{background:#1da851;transform:translateY(-2px);color:#fff!important}
    .pds-share-btn-twitter{background:#1DA1F2;color:#fff!important}.pds-share-btn-twitter:hover{background:#0c8de4;transform:translateY(-2px);color:#fff!important}
    .pds-share-btn-linkedin{background:#0A66C2;color:#fff!important}.pds-share-btn-linkedin:hover{background:#084d94;transform:translateY(-2px);color:#fff!important}
    .pds-modal-bg{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,.9);backdrop-filter:blur(8px);z-index:999999;display:none;align-items:center;justify-content:center;padding:20px}.pds-modal-bg.show{display:flex}
    .pds-modal-container{background:var(--pds-card);border-radius:18px;max-width:450px;width:100%;max-height:90vh;overflow-y:auto;animation:pdsModalIn .4s ease;box-shadow:0 20px 60px rgba(0,0,0,.4)}@keyframes pdsModalIn{from{opacity:0;transform:scale(.9) translateY(30px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .pds-modal-head{padding:16px 20px;border-bottom:1px solid var(--pds-border);display:flex;justify-content:space-between;align-items:center;background:#fafafa}.pds-modal-title{font-size:1rem;font-weight:800;color:var(--pds-primary)}
    .pds-modal-close{width:34px;height:34px;border-radius:50%;border:none;background:var(--pds-card);cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:all .3s;color:var(--pds-text-light)}.pds-modal-close:hover{background:#ef4444;color:#fff!important;transform:rotate(90deg)}
    .pds-modal-content{padding:20px;direction:<?php echo $dir;?>;text-align:<?php echo $text_align;?>}
    .pds-success{display:none;text-align:center;padding:35px 20px}.pds-success.show{display:block}.pds-success-icon{width:70px;height:70px;margin:0 auto 18px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;animation:pdsSuccessPop .5s ease}@keyframes pdsSuccessPop{0%{transform:scale(0)}50%{transform:scale(1.2)}100%{transform:scale(1)}}.pds-success-icon svg{width:35px;height:35px;color:#fff}.pds-success h3{font-size:1.2rem;color:var(--pds-primary);margin-bottom:8px;font-weight:800}.pds-success p{color:var(--pds-text-light);font-size:.9rem;margin-bottom:18px}
    .pds-success-btn{padding:12px 28px;background:var(--pds-primary);color:#fff!important;border-radius:8px;font-weight:700;cursor:pointer;border:none;transition:all .3s}.pds-success-btn:hover{background:var(--pds-primary-light);color:#fff!important}
    .pds-lightbox{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.95);z-index:9999999;display:none;align-items:center;justify-content:center;padding:20px}.pds-lightbox.show{display:flex}.pds-lightbox-img{max-width:90%;max-height:90vh;object-fit:contain;border-radius:8px;animation:pdsLightIn .3s ease}@keyframes pdsLightIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
    .pds-lightbox-close{position:absolute;top:20px;right:20px;width:45px;height:45px;border-radius:50%;background:rgba(255,255,255,.1);color:#fff;border:none;cursor:pointer;font-size:22px;display:flex;align-items:center;justify-content:center;transition:all .3s}.pds-lightbox-close:hover{background:#ef4444;transform:rotate(90deg)}
    @media(max-width:768px){.pds-wrapper{padding:15px 12px}.pds-header{flex-direction:column;align-items:stretch}.pds-back-btn,.pds-lang-btn{justify-content:center;text-align:center}.pds-title{font-size:1.4rem}.pds-meta-grid{grid-template-columns:1fr;padding:18px}.pds-gallery-main{padding-bottom:65%}.pds-gallery-nav-btn{width:36px;height:36px;font-size:14px}.pds-thumb{width:60px;height:48px}.pds-details{padding:20px 15px}.pds-details-content{font-size:.95rem}.pds-actions{flex-direction:column}.pds-action-btn{min-width:unset;width:100%}.pds-share-btns{flex-direction:column}.pds-share-btn{justify-content:center}.pds-modal-container{max-width:95%;border-radius:14px}}
    @media(max-width:480px){.pds-featured-badge{font-size:.78rem;padding:8px 16px}.pds-featured-badge img{width:24px;height:24px}.pds-tag{padding:6px 12px;font-size:.78rem}.pds-meta-icon{width:38px;height:38px}.pds-meta-icon svg{width:18px;height:18px}}
    </style>
    <div class="pds-wrapper">
        <div class="pds-header">
            <a href="<?php echo esc_url($page_url); ?>" class="pds-back-btn"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M<?php echo $is_english?'19 12H5M12 19l-7-7 7-7':'5 12h14M12 5l7 7-7 7';?>"/></svg><?php echo $texts['back']; ?></a>
            <a href="<?php echo esc_url($other_lang_url); ?>" class="pds-lang-btn"><?php echo $other_lang_text; ?></a>
        </div>
<?php if ($pdata['is_featured'] === '1'): ?><div class="pds-featured-badge" style="background:linear-gradient(135deg,rgba(91,163,217,.15),rgba(160,207,239,.1));border:2px solid #5ba3d9;box-shadow:0 4px 15px rgba(91,163,217,.3);"><img src="http://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp" alt="Vibes" style="border-radius:50%;"><span><?php echo $texts['featured_badge']; ?></span></div><?php endif; ?>
        <h1 class="pds-title"><?php echo esc_html($pdata['title']); ?></h1>
        <div class="pds-tags">
            <?php if ($sector_display): ?><span class="pds-tag pds-tag-sector"><?php echo esc_html($sector_display); ?></span><?php endif; ?>
            <?php if ($stage_display): ?><span class="pds-tag pds-tag-stage"><?php echo esc_html($stage_display); ?></span><?php endif; ?>
        </div>
        <div class="pds-meta-grid">
            <?php if ($pdata['founder_name']): ?><div class="pds-meta-item"><div class="pds-meta-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div><div class="pds-meta-content"><span class="pds-meta-label"><?php echo $texts['founder']; ?></span><span class="pds-meta-value"><?php echo esc_html($pdata['founder_name']); ?></span></div></div><?php endif; ?>
            <?php if ($pdata['company_name']): ?><div class="pds-meta-item"><div class="pds-meta-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div><div class="pds-meta-content"><span class="pds-meta-label"><?php echo $texts['company']; ?></span><span class="pds-meta-value"><?php echo esc_html($pdata['company_name']); ?></span></div></div><?php endif; ?>
            <?php if ($pdata['website']): ?><div class="pds-meta-item"><div class="pds-meta-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></div><div class="pds-meta-content"><span class="pds-meta-label"><?php echo $texts['visit_website']; ?></span><span class="pds-meta-value"><a href="<?php echo esc_url($pdata['website']); ?>" target="_blank"><?php echo esc_html(parse_url($pdata['website'], PHP_URL_HOST)); ?></a></span></div></div><?php endif; ?>
            <div class="pds-meta-item"><div class="pds-meta-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></div><div class="pds-meta-content"><span class="pds-meta-label"><?php echo $texts['views']; ?></span><span class="pds-meta-value"><?php echo number_format($views); ?></span></div></div>
        </div>
        <?php if (!empty($gallery_images)): ?>
        <div class="pds-gallery">
            <h2 class="pds-section-title"><?php echo $texts['project_gallery']; ?></h2>
            <div class="pds-gallery-main" id="pdsGalleryMain">
                <img src="<?php echo esc_url($gallery_images[0]); ?>" alt="<?php echo esc_attr($pdata['title']); ?>" class="pds-gallery-main-img" id="pdsMainImg" onclick="pdsOpenLightbox()">
                <?php if (count($gallery_images) > 1): ?>
                <div class="pds-gallery-nav"><button type="button" class="pds-gallery-nav-btn" onclick="pdsGalleryPrev()">❮</button><button type="button" class="pds-gallery-nav-btn" onclick="pdsGalleryNext()">❯</button></div>
                <div class="pds-gallery-counter"><span id="pdsGalleryIdx">1</span> / <?php echo count($gallery_images); ?></div>
                <?php endif; ?>
            </div>
            <?php if (count($gallery_images) > 1): ?>
            <div class="pds-gallery-thumbs">
                <?php foreach ($gallery_images as $idx => $img): ?><div class="pds-thumb <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="pdsGalleryGoTo(<?php echo $idx; ?>)" data-index="<?php echo $idx; ?>"><img src="<?php echo esc_url($img); ?>" alt=""></div><?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($pdata['project_details']): ?>
        <div class="pds-details"><h2 class="pds-section-title"><?php echo $texts['project_details']; ?></h2><div class="pds-details-content"><?php echo wpautop(esc_html($pdata['project_details'])); ?></div></div>
        <?php endif; ?>
        <div class="pds-actions">
            <?php if ($pdata['pitch_url']): ?><button type="button" class="pds-action-btn pds-btn-pitch" onclick="pdsOpenPitchModal()"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><?php echo $texts['download_pitch']; ?></button><?php endif; ?>
            <button type="button" class="pds-action-btn pds-btn-contact" onclick="pdsOpenContactModal()"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><?php echo $texts['contact_founder']; ?></button>
        </div>
        <div class="pds-share">
            <h3 class="pds-share-title"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg><?php echo $texts['share_project']; ?></h3>
            <div class="pds-share-btns">
                <button type="button" class="pds-share-btn pds-share-btn-copy" id="pdsCopyBtn" onclick="pdsCopyLink()"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg><span id="pdsCopyTxt"><?php echo $texts['copy_link']; ?></span></button>
                <a href="https://wa.me/?text=<?php echo urlencode($pdata['title'].' - '.$current_url); ?>" target="_blank" class="pds-share-btn pds-share-btn-whatsapp"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>WhatsApp</a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url); ?>&text=<?php echo urlencode($pdata['title']); ?>" target="_blank" class="pds-share-btn pds-share-btn-twitter"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>X</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($current_url); ?>" target="_blank" class="pds-share-btn pds-share-btn-linkedin"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>LinkedIn</a>
            </div>
        </div>
    </div>
    <div class="pds-lightbox" id="pdsLightbox" onclick="pdsCloseLightbox()"><button type="button" class="pds-lightbox-close">×</button><img src="" alt="" class="pds-lightbox-img" id="pdsLightImg" onclick="event.stopPropagation()"></div>
    <div class="pds-modal-bg" id="pdsPitchModal"><div class="pds-modal-container"><div class="pds-modal-head"><h3 class="pds-modal-title"><?php echo $texts['download_pitch']; ?></h3><button type="button" class="pds-modal-close" onclick="pdsClosePitchModal()">×</button></div><div class="pds-modal-content"><div id="pdsPitchForm"><?php echo do_shortcode($is_english ? '[fluentform id="32"]' : '[fluentform id="31"]'); ?></div><div class="pds-success" id="pdsPitchSuccess"><div class="pds-success-icon"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div><h3><?php echo $texts['form_success']; ?></h3><p><?php echo $texts['form_success_msg']; ?></p><button type="button" class="pds-success-btn" onclick="pdsClosePitchModal()"><?php echo $texts['close']; ?></button></div></div></div></div>
    <div class="pds-modal-bg" id="pdsContactModal"><div class="pds-modal-container"><div class="pds-modal-head"><h3 class="pds-modal-title"><?php echo $texts['contact_founder']; ?></h3><button type="button" class="pds-modal-close" onclick="pdsCloseContactModal()">×</button></div><div class="pds-modal-content"><?php echo do_shortcode($is_english ? '[fluentform id="33"]' : '[fluentform id="30"]'); ?></div></div></div>
    <script>
    (function(){var projectData={title:'<?php echo esc_js($pdata['title']);?>',pitchUrl:'<?php echo esc_js($pdata['pitch_url']);?>',founder:'<?php echo esc_js($pdata['founder_name']);?>',whatsapp:'<?php echo esc_js($pdata['whatsapp']);?>',url:'<?php echo esc_js($current_url);?>',copied:'<?php echo esc_js($texts['link_copied']);?>',copy:'<?php echo esc_js($texts['copy_link']);?>'};
    var galleryImages=<?php echo json_encode($gallery_images);?>,currentIdx=0;
    window.pdsGalleryGoTo=function(idx){currentIdx=idx;updateGallery()};window.pdsGalleryNext=function(){currentIdx=(currentIdx+1)%galleryImages.length;updateGallery()};window.pdsGalleryPrev=function(){currentIdx=(currentIdx-1+galleryImages.length)%galleryImages.length;updateGallery()};
    function updateGallery(){var mainImg=document.getElementById('pdsMainImg'),idxEl=document.getElementById('pdsGalleryIdx'),thumbs=document.querySelectorAll('.pds-thumb');if(mainImg&&galleryImages[currentIdx]){mainImg.style.opacity='0';setTimeout(function(){mainImg.src=galleryImages[currentIdx];mainImg.style.opacity='1'},200)}if(idxEl)idxEl.textContent=currentIdx+1;thumbs.forEach(function(t,i){t.classList.toggle('active',i===currentIdx)})}
    window.pdsOpenLightbox=function(){var lb=document.getElementById('pdsLightbox'),img=document.getElementById('pdsLightImg');if(lb&&img&&galleryImages[currentIdx]){img.src=galleryImages[currentIdx];lb.classList.add('show');document.body.style.overflow='hidden'}};
    window.pdsCloseLightbox=function(){var lb=document.getElementById('pdsLightbox');if(lb){lb.classList.remove('show');document.body.style.overflow=''}};
    window.pdsCopyLink=function(){var btn=document.getElementById('pdsCopyBtn'),txt=document.getElementById('pdsCopyTxt');navigator.clipboard.writeText(projectData.url).then(function(){if(btn)btn.classList.add('copied');if(txt)txt.textContent=projectData.copied;setTimeout(function(){if(btn)btn.classList.remove('copied');if(txt)txt.textContent=projectData.copy},2000)})};
    function fillFields(modalId){var modal=document.getElementById(modalId);if(!modal)return;var fields=[{names:['project_name','project-name','names_1'],val:projectData.title},{names:['pitch_url','pitch-url','names_2'],val:projectData.pitchUrl},{names:['founder_name','founder-name','names_3'],val:projectData.founder},{names:['founder_whatsapp','whatsapp','names_4'],val:projectData.whatsapp}];fields.forEach(function(f){f.names.forEach(function(n){var el=modal.querySelector('input[name="'+n+'"]');if(el)el.value=f.val||''})})}
    window.pdsOpenPitchModal=function(){fillFields('pdsPitchModal');var form=document.getElementById('pdsPitchForm'),success=document.getElementById('pdsPitchSuccess');if(form)form.style.display='block';if(success)success.classList.remove('show');var modal=document.getElementById('pdsPitchModal');if(modal){modal.classList.add('show');document.body.style.overflow='hidden'}};
    window.pdsClosePitchModal=function(){var modal=document.getElementById('pdsPitchModal');if(modal){modal.classList.remove('show');document.body.style.overflow=''}setTimeout(function(){var form=document.getElementById('pdsPitchForm'),success=document.getElementById('pdsPitchSuccess');if(form)form.style.display='block';if(success)success.classList.remove('show')},300)};
    window.pdsOpenContactModal=function(){fillFields('pdsContactModal');var modal=document.getElementById('pdsContactModal');if(modal){modal.classList.add('show');document.body.style.overflow='hidden'}};
    window.pdsCloseContactModal=function(){var modal=document.getElementById('pdsContactModal');if(modal){modal.classList.remove('show');document.body.style.overflow=''}};
    document.querySelectorAll('.pds-modal-bg').forEach(function(m){m.addEventListener('click',function(e){if(e.target===this){this.classList.remove('show');document.body.style.overflow=''}})});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){pdsCloseLightbox();pdsClosePitchModal();pdsCloseContactModal()}if(galleryImages.length>1){if(e.key==='ArrowRight')pdsGalleryNext();if(e.key==='ArrowLeft')pdsGalleryPrev()}});
    if(typeof jQuery!=='undefined'){jQuery(document).on('fluentform_submission_success',function(e,r,form){if(jQuery(form).closest('#pdsPitchModal').length>0){var f=document.getElementById('pdsPitchForm'),s=document.getElementById('pdsPitchSuccess');if(f)f.style.display='none';if(s)s.classList.add('show')}})}})();
    </script>
    <?php
    return ob_get_clean();
}
// ========== 17. Admin Scripts ==========
add_action('admin_enqueue_scripts', 'pdp_admin_scripts');
function pdp_admin_scripts($hook) {
    global $post_type;
    if ($post_type === 'project') wp_enqueue_media();
}

// ========== 18. أعمدة الأدمن ==========
add_filter('manage_project_posts_columns', 'pdp_add_admin_columns');
function pdp_add_admin_columns($columns) {
    $new = [];
    foreach ($columns as $key => $value) {
        $new[$key] = $value;
        if ($key === 'title') {
            $new['project_thumb'] = '📷';
            $new['is_featured'] = '⭐';
            $new['featured_order'] = 'ترتيب';
            $new['has_english'] = '🇬🇧';
            $new['has_pitch'] = '📄';
            $new['views_count'] = '👁️';
            $new['project_sector'] = 'القطاع';
        }
    }
    return $new;
}

add_action('manage_project_posts_custom_column', 'pdp_admin_column_content', 10, 2);
function pdp_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'project_thumb':
            echo has_post_thumbnail($post_id) ? '<img src="'.get_the_post_thumbnail_url($post_id,'thumbnail').'" style="width:45px;height:45px;object-fit:cover;border-radius:6px;">' : '<span style="color:#ccc;">—</span>';
            break;
        case 'is_featured':
            echo get_post_meta($post_id,'is_featured',true)==='1' ? '<span style="color:#d4a853;font-size:1.3em;" title="مشروع مميز">⭐</span>' : '<span style="color:#ddd;font-size:1.3em;cursor:pointer;" title="اضغط للتمييز" onclick="pdpQuickFeature('.$post_id.')">☆</span>';
            break;
        case 'featured_order':
            if (get_post_meta($post_id,'is_featured',true)==='1') { $order = get_post_meta($post_id,'featured_order',true)?:'10'; echo '<span style="background:#d4a853;color:#1D2D51;padding:3px 10px;border-radius:12px;font-weight:700;font-size:.8rem;">'.esc_html($order).'</span>'; } else echo '<span style="color:#ccc;">—</span>';
            break;
        case 'has_english':
            $t = get_post_meta($post_id,'title_en',true); $d = get_post_meta($post_id,'project_details_en',true);
            echo (!empty($t)||!empty($d)) ? '<span style="color:#10b981;" title="يحتوي إنجليزي">✓</span>' : '<span style="color:#ef4444;" title="بدون إنجليزي">✗</span>';
            break;
        case 'has_pitch':
            $p = get_post_meta($post_id,'pitch_deck',true);
            echo !empty($p) ? '<a href="'.esc_url(wp_get_attachment_url($p)).'" target="_blank" style="color:#3b82f6;" title="عرض PDF">📄</a>' : '<span style="color:#ccc;">—</span>';
            break;
        case 'views_count':
            $v = (int)get_post_meta($post_id,'views_count',true); $c = $v>100?'#10b981':($v>50?'#f59e0b':'#64748b');
            echo '<span style="color:'.$c.';font-weight:600;">'.number_format($v).'</span>';
            break;
        case 'project_sector':
            $s = get_the_terms($post_id,'sector');
            echo ($s&&!is_wp_error($s)) ? '<span style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-size:.75rem;">'.esc_html($s[0]->name).'</span>' : '<span style="color:#ccc;">—</span>';
            break;
    }
}

// ========== 19. أعمدة قابلة للترتيب ==========
add_filter('manage_edit-project_sortable_columns', 'pdp_sortable_columns');
function pdp_sortable_columns($columns) {
    $columns['is_featured'] = 'is_featured';
    $columns['featured_order'] = 'featured_order';
    $columns['views_count'] = 'views_count';
    return $columns;
}

add_action('pre_get_posts', 'pdp_orderby_columns');
function pdp_orderby_columns($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    $ob = $query->get('orderby');
    if ($ob === 'is_featured') { $query->set('meta_key','is_featured'); $query->set('orderby','meta_value'); }
    elseif ($ob === 'featured_order') { $query->set('meta_key','featured_order'); $query->set('orderby','meta_value_num'); }
    elseif ($ob === 'views_count') { $query->set('meta_key','views_count'); $query->set('orderby','meta_value_num'); }
}

// ========== 20. فلاتر الأدمن ==========
add_action('restrict_manage_posts', 'pdp_admin_filters');
function pdp_admin_filters($post_type) {
    if ($post_type !== 'project') return;
    $featured = isset($_GET['featured_filter']) ? $_GET['featured_filter'] : '';
    $has_english = isset($_GET['english_filter']) ? $_GET['english_filter'] : '';
    $has_pitch = isset($_GET['pitch_filter']) ? $_GET['pitch_filter'] : '';
    ?>
    <select name="featured_filter"><option value="">كل المشاريع</option><option value="1" <?php selected($featured,'1');?>>⭐ المميزة</option><option value="0" <?php selected($featured,'0');?>>العادية</option></select>
    <select name="english_filter"><option value="">كل اللغات</option><option value="1" <?php selected($has_english,'1');?>>🇬🇧 بها إنجليزي</option><option value="0" <?php selected($has_english,'0');?>>بدون إنجليزي</option></select>
    <select name="pitch_filter"><option value="">Pitch Deck</option><option value="1" <?php selected($has_pitch,'1');?>>📄 يوجد</option><option value="0" <?php selected($has_pitch,'0');?>>لا يوجد</option></select>
    <?php
}

add_action('pre_get_posts', 'pdp_admin_filter_query');
function pdp_admin_filter_query($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'project') return;
    $meta_query = [];
    if (isset($_GET['featured_filter']) && $_GET['featured_filter'] !== '') {
        $meta_query[] = ['key'=>'is_featured','value'=>$_GET['featured_filter'],'compare'=>'='];
    }
    if (isset($_GET['english_filter']) && $_GET['english_filter'] !== '') {
        if ($_GET['english_filter'] === '1') {
            $meta_query[] = ['relation'=>'OR',['key'=>'title_en','value'=>'','compare'=>'!='],['key'=>'project_details_en','value'=>'','compare'=>'!=']];
        } else {
            $meta_query[] = ['relation'=>'AND',['relation'=>'OR',['key'=>'title_en','compare'=>'NOT EXISTS'],['key'=>'title_en','value'=>'','compare'=>'=']],['relation'=>'OR',['key'=>'project_details_en','compare'=>'NOT EXISTS'],['key'=>'project_details_en','value'=>'','compare'=>'=']]];
        }
    }
    if (isset($_GET['pitch_filter']) && $_GET['pitch_filter'] !== '') {
        if ($_GET['pitch_filter'] === '1') { $meta_query[] = ['key'=>'pitch_deck','value'=>'','compare'=>'!=']; }
        else { $meta_query[] = ['relation'=>'OR',['key'=>'pitch_deck','compare'=>'NOT EXISTS'],['key'=>'pitch_deck','value'=>'','compare'=>'=']]; }
    }
    if (!empty($meta_query)) { $meta_query['relation'] = 'AND'; $query->set('meta_query', $meta_query); }
}

// ========== 21. إحصائيات الأدمن ==========
add_action('admin_footer-edit.php', 'pdp_admin_stats_footer');
function pdp_admin_stats_footer() {
    global $post_type, $wpdb;
    if ($post_type !== 'project') return;
    $total = wp_count_posts('project')->publish;
    $stats = get_transient('pdp_admin_stats');
    if ($stats === false) {
        $fc = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key='is_featured' AND meta_value='1'");
        $ec = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ('title_en','project_details_en') AND meta_value!=''");
        $tv = $wpdb->get_var("SELECT COALESCE(SUM(CAST(meta_value AS UNSIGNED)),0) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id=p.ID WHERE pm.meta_key='views_count' AND p.post_type='project' AND p.post_status='publish'");
        $stats = ['featured'=>(int)$fc,'english'=>(int)$ec,'views'=>(int)$tv];
        set_transient('pdp_admin_stats', $stats, 5 * MINUTE_IN_SECONDS);
    }
    ?>
    <script>
    jQuery(document).ready(function($){
        var s='<div style="background:linear-gradient(135deg,#1D2D51,#2a4a7f);color:#fff;padding:18px 22px;border-radius:10px;margin:18px 0;display:flex;gap:25px;flex-wrap:wrap;align-items:center;box-shadow:0 4px 15px rgba(29,45,81,.25);">';
        s+='<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:900;"><?php echo $total;?></div><div style="font-size:.8rem;opacity:.8;">إجمالي</div></div>';
        s+='<div style="width:1px;height:40px;background:rgba(255,255,255,.2);"></div>';
        s+='<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:900;color:#f0d78c;">⭐ <?php echo $stats['featured'];?></div><div style="font-size:.8rem;opacity:.8;">مميزة</div></div>';
        s+='<div style="width:1px;height:40px;background:rgba(255,255,255,.2);"></div>';
        s+='<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:900;color:#60a5fa;">🇬🇧 <?php echo $stats['english'];?></div><div style="font-size:.8rem;opacity:.8;">إنجليزي</div></div>';
        s+='<div style="width:1px;height:40px;background:rgba(255,255,255,.2);"></div>';
        s+='<div style="text-align:center;"><div style="font-size:1.8rem;font-weight:900;color:#10b981;">👁️ <?php echo number_format($stats['views']);?></div><div style="font-size:.8rem;opacity:.8;">مشاهدات</div></div>';
        s+='</div>';
        $('.wrap h1').after(s);
    });
    </script>
    <?php
}

// ========== 22. AJAX للتمييز السريع ==========
add_action('wp_ajax_pdp_quick_feature', 'pdp_ajax_quick_feature');
function pdp_ajax_quick_feature() {
    check_ajax_referer('pdp_quick_feature_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('غير مصرح');
    $post_id = intval($_POST['post_id']);
    $current = get_post_meta($post_id, 'is_featured', true);
    $new_value = ($current === '1') ? '0' : '1';
    update_post_meta($post_id, 'is_featured', $new_value);
    if ($new_value === '1' && !get_post_meta($post_id, 'featured_order', true)) update_post_meta($post_id, 'featured_order', '10');
    wp_send_json_success(['is_featured' => $new_value]);
}

add_action('admin_footer-edit.php', 'pdp_quick_feature_script');
function pdp_quick_feature_script() {
    global $post_type;
    if ($post_type !== 'project') return;
    $nonce = wp_create_nonce('pdp_quick_feature_nonce');
    ?>
    <script>
    function pdpQuickFeature(postId){if(!confirm('تغيير حالة التمييز؟'))return;jQuery.ajax({url:ajaxurl,type:'POST',data:{action:'pdp_quick_feature',post_id:postId,nonce:'<?php echo $nonce;?>'},success:function(r){if(r.success)location.reload();else alert('خطأ')},error:function(){alert('خطأ في الاتصال')}})}
    </script>
    <?php
}

// ========== 23. صفحة الإعدادات ==========
add_action('admin_menu', 'pdp_add_settings_page');
function pdp_add_settings_page() {
    add_submenu_page('edit.php?post_type=project', 'إعدادات بنك المشاريع', '⚙️ الإعدادات', 'manage_options', 'pdp-settings', 'pdp_render_settings_page');
}

function pdp_render_settings_page() {
    $ar_page = get_page_by_path('pb');
    $en_page = get_page_by_path('pb-eng');
    $total = wp_count_posts('project')->publish;
    $version = get_option('pdp_version', '32.0');
    $logo_badge = 'http://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp';
    // معالجة الأزرار
    if (isset($_POST['pdp_boost_views_now']) && wp_verify_nonce($_POST['pdp_boost_nonce'], 'pdp_boost_views_now')) {
        pdp_boost_all_projects_views();
        echo '<div class="notice notice-success"><p>✅ تم زيادة المشاهدات بنجاح!</p></div>';
    }
    if (isset($_POST['pdp_reset_views']) && wp_verify_nonce($_POST['pdp_reset_nonce'], 'pdp_reset_views')) {
        global $wpdb;
        $wpdb->query("UPDATE {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id=p.ID SET pm.meta_value='0' WHERE pm.meta_key='views_count' AND p.post_type='project'");
        echo '<div class="notice notice-success"><p>✅ تم إعادة تعيين المشاهدات!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:12px;">
            <span style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#e8f4fd 0%,#d0e8fa 30%,#b8dcf8 60%,#a0cfef 100%);display:inline-flex;align-items:center;justify-content:center;border:2px solid #5ba3d9;box-shadow:0 2px 10px rgba(91,163,217,.3);">
                <img src="<?php echo esc_url($logo_badge); ?>" style="width:28px;height:28px;border-radius:50%;object-fit:contain;">
            </span>
            بنك المشاريع - الإعدادات
        </h1>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:25px;">
            <div style="background:#fff;border-radius:12px;padding:25px;box-shadow:0 2px 15px rgba(0,0,0,.06);border:1px solid #e2e8f0;">
                <h2 style="margin-top:0;color:#1D2D51;font-size:1.1rem;">📊 معلومات البلجن</h2>
                <table style="width:100%;"><tr><td style="padding:10px 0;color:#64748b;">الإصدار</td><td style="font-weight:700;color:#1D2D51;"><?php echo esc_html($version); ?></td></tr><tr><td style="padding:10px 0;color:#64748b;">عدد المشاريع</td><td style="font-weight:700;color:#10b981;"><?php echo $total; ?></td></tr></table>
            </div>
            <div style="background:#fff;border-radius:12px;padding:25px;box-shadow:0 2px 15px rgba(0,0,0,.06);border:1px solid #e2e8f0;">
                <h2 style="margin-top:0;color:#1D2D51;font-size:1.1rem;">🔗 روابط الصفحات</h2>
                <div style="margin-bottom:15px;">
                    <label style="display:block;font-weight:600;margin-bottom:6px;color:#475569;font-size:.85rem;">🇸🇦 الصفحة العربية</label>
                    <?php if ($ar_page): ?><div style="display:flex;gap:8px;"><input type="text" value="<?php echo get_permalink($ar_page); ?>" readonly style="flex:1;padding:8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-size:.85rem;"><a href="<?php echo get_permalink($ar_page); ?>" target="_blank" style="padding:8px 16px;background:#1D2D51;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;font-size:.85rem;">زيارة</a></div><?php else: ?><p style="color:#ef4444;font-size:.85rem;">❌ غير موجودة</p><?php endif; ?>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;color:#475569;font-size:.85rem;">🇬🇧 الصفحة الإنجليزية</label>
                    <?php if ($en_page): ?><div style="display:flex;gap:8px;"><input type="text" value="<?php echo get_permalink($en_page); ?>" readonly style="flex:1;padding:8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-size:.85rem;"><a href="<?php echo get_permalink($en_page); ?>" target="_blank" style="padding:8px 16px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;font-size:.85rem;">Visit</a></div><?php else: ?><p style="color:#ef4444;font-size:.85rem;">❌ Not found</p><?php endif; ?>
                </div>
            </div>
            <div style="background:#fff;border-radius:12px;padding:25px;box-shadow:0 2px 15px rgba(0,0,0,.06);border:1px solid #e2e8f0;">
                <h2 style="margin-top:0;color:#1D2D51;font-size:1.1rem;">📝 Shortcodes</h2>
                <div style="margin-bottom:12px;"><label style="display:block;font-weight:600;margin-bottom:5px;color:#475569;font-size:.8rem;">العربية:</label><code style="display:block;padding:10px;background:#f8fafc;border-radius:6px;font-size:.8rem;color:#1D2D51;">[projects_directory]</code></div>
                <div><label style="display:block;font-weight:600;margin-bottom:5px;color:#475569;font-size:.8rem;">English:</label><code style="display:block;padding:10px;background:#f8fafc;border-radius:6px;font-size:.8rem;color:#2563eb;">[projects_directory_en]</code></div>
            </div>
            <div style="background:linear-gradient(135deg,#1D2D51,#2a4a7f);border-radius:12px;padding:25px;color:#fff;">
                <h2 style="margin-top:0;color:#f0d78c;font-size:1.1rem;">💡 الدعم</h2>
                <p style="opacity:.9;font-size:.9rem;line-height:1.7;">تم التطوير بواسطة فايبز القابضة</p>
                <div style="margin-top:15px;"><a href="https://vibesholding.com/" target="_blank" style="padding:8px 16px;background:rgba(255,255,255,.15);color:#fff;border-radius:6px;text-decoration:none;font-weight:600;font-size:.85rem;">🌐 الموقع</a></div>
            </div>
        </div>
        <div style="margin-top:30px;background:#fff;border-radius:12px;padding:25px;box-shadow:0 2px 15px rgba(0,0,0,.06);border:1px solid #e2e8f0;">
            <h2 style="margin-top:0;color:#1D2D51;font-size:1.1rem;">🔧 أدوات</h2>
            <div style="display:flex;gap:15px;flex-wrap:wrap;margin-top:15px;">
                <form method="post" style="display:inline;"><?php wp_nonce_field('pdp_boost_views_now','pdp_boost_nonce'); ?><button type="submit" name="pdp_boost_views_now" class="button button-secondary">👁️ زيادة المشاهدات الآن</button></form>
                <form method="post" style="display:inline;" onsubmit="return confirm('هل أنت متأكد؟ سيتم إعادة تعيين كل المشاهدات لـ 0');"><?php wp_nonce_field('pdp_reset_views','pdp_reset_nonce'); ?><button type="submit" name="pdp_reset_views" class="button" style="color:#ef4444;">🗑️ إعادة تعيين المشاهدات</button></form>
            </div>
        </div>
    </div>
    <?php
}

// ========== 25. تفعيل البلجن ==========
register_activation_hook(__FILE__, 'pdp_activate');
function pdp_activate() {
    pdp_register_cpt();
    pdp_add_default_terms();
    pdp_create_projects_pages();
    if (!wp_next_scheduled('pdp_daily_views_boost')) wp_schedule_event(time(), 'daily', 'pdp_daily_views_boost');
    flush_rewrite_rules();
    update_option('pdp_version', '32.0');
}

// ========== 26. إلغاء تفعيل البلجن ==========
register_deactivation_hook(__FILE__, 'pdp_deactivate');
function pdp_deactivate() {
    wp_clear_scheduled_hook('pdp_daily_views_boost');
    flush_rewrite_rules();
}

// ========== 27. حذف البلجن ==========
register_uninstall_hook(__FILE__, 'pdp_uninstall');
function pdp_uninstall() {
    delete_option('pdp_version');
    wp_clear_scheduled_hook('pdp_daily_views_boost');
}

// ========== 28. السلايدر للصور ==========
add_action('wp_footer', 'pdp_auto_slider_script');
function pdp_auto_slider_script() {
    global $post;
    if (!$post || (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en'))) return;
    ?>
    <style>
    .pdp-card-image{position:relative;overflow:hidden}.pdp-slider-container{position:absolute;top:0;left:0;width:100%;height:100%}.pdp-slide{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;transition:opacity .6s ease}.pdp-slide.active{opacity:1}.pdp-slide img{width:100%;height:100%;object-fit:cover}.pdp-slider-dots{position:absolute;bottom:8px;left:50%;transform:translateX(-50%);display:flex;gap:5px;z-index:15;padding:4px 8px;background:rgba(0,0,0,.3);border-radius:15px}.pdp-slider-dot{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.5);cursor:pointer;border:none;padding:0;transition:all .3s}.pdp-slider-dot.active{background:#d4a853;transform:scale(1.2)}.pdp-slider-nav{position:absolute;top:50%;transform:translateY(-50%);width:100%;display:flex;justify-content:space-between;padding:0 6px;z-index:15;pointer-events:none;opacity:0;transition:opacity .3s}.pdp-card-image:hover .pdp-slider-nav{opacity:1}.pdp-slider-btn{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.9);color:#1D2D51;border:none;cursor:pointer;pointer-events:all;transition:all .3s;font-size:12px;font-weight:bold}.pdp-slider-btn:hover{background:#d4a853;color:#fff}@media(max-width:768px){.pdp-slider-nav{opacity:1}.pdp-slider-btn{width:24px;height:24px;font-size:10px}}
    </style>
    <script>
    (function(){var sliders=[],interval=3500;function init(){document.querySelectorAll('.pdp-project-card[data-gallery]').forEach(function(card){var container=card.querySelector('.pdp-card-image');if(!container||container.querySelector('.pdp-slider-container'))return;var data=card.getAttribute('data-gallery');if(!data)return;try{var images=JSON.parse(data)}catch(e){return}if(!images||images.length<2)return;var html='<div class="pdp-slider-container">';images.forEach(function(src,j){html+='<div class="pdp-slide'+(j===0?' active':'')+'"><img src="'+src+'" loading="lazy"></div>'});html+='<div class="pdp-slider-nav"><button type="button" class="pdp-slider-btn pdp-prev">❮</button><button type="button" class="pdp-slider-btn pdp-next">❯</button></div><div class="pdp-slider-dots">';images.forEach(function(_,j){html+='<button type="button" class="pdp-slider-dot'+(j===0?' active':'')+'" data-i="'+j+'"></button>'});html+='</div></div>';var oldImg=container.querySelector('img');if(oldImg)oldImg.remove();container.insertAdjacentHTML('beforeend',html);var slider={el:container,idx:0,total:images.length,paused:false};sliders.push(slider);container.querySelector('.pdp-prev').onclick=function(e){e.stopPropagation();go(slider,slider.idx-1)};container.querySelector('.pdp-next').onclick=function(e){e.stopPropagation();go(slider,slider.idx+1)};container.querySelectorAll('.pdp-slider-dot').forEach(function(d){d.onclick=function(e){e.stopPropagation();go(slider,parseInt(this.dataset.i))}});container.onmouseenter=function(){slider.paused=true};container.onmouseleave=function(){slider.paused=false}});setInterval(function(){sliders.forEach(function(s){if(!s.paused)go(s,s.idx+1)})},interval)}function go(s,i){if(i<0)i=s.total-1;else if(i>=s.total)i=0;s.el.querySelectorAll('.pdp-slide').forEach(function(sl,j){sl.classList.toggle('active',j===i)});s.el.querySelectorAll('.pdp-slider-dot').forEach(function(d,j){d.classList.toggle('active',j===i)});s.idx=i}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(init,100)});else setTimeout(init,100)})();
    </script>
    <?php
}

// ========== 29. أزرار المشاركة السريعة على الكروت ==========
add_action('wp_footer', 'pdp_add_share_buttons_script');
function pdp_add_share_buttons_script() {
    global $post;
    if (!$post || (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en'))) return;
    ?>
    <style>
    .pdp-project-card{position:relative}.pdp-share-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(29,45,81,.92);backdrop-filter:blur(10px);display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:all .4s cubic-bezier(.4,0,.2,1);z-index:50;border-radius:var(--pdp-radius,16px);padding:20px 15px;gap:12px}.pdp-project-card:hover .pdp-share-overlay{opacity:1;visibility:visible}
    .pdp-share-close-mobile{position:absolute;top:12px;right:12px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;border:none;cursor:pointer;display:none;align-items:center;justify-content:center;font-size:18px;transition:all .3s}.pdp-share-close-mobile:hover{background:rgba(239,68,68,.8)}
    .pdp-main-actions{display:flex;flex-direction:column;gap:10px;width:100%;max-width:280px}
    .pdp-overlay-btn-main{display:flex;align-items:center;justify-content:center;gap:10px;padding:14px 24px;border-radius:12px;font-weight:800;font-size:.95rem;text-decoration:none;cursor:pointer;border:none;transition:all .3s ease;width:100%;text-align:center}.pdp-overlay-btn-main svg{width:22px;height:22px;flex-shrink:0}.pdp-overlay-btn-main:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(0,0,0,.3)}
    .pdp-btn-overlay-details{background:linear-gradient(135deg,#d4a853,#f0d78c);color:#1D2D51!important}.pdp-btn-overlay-details:hover{background:linear-gradient(135deg,#c49a4a,#e6c86e);color:#1D2D51!important}
    .pdp-btn-overlay-pitch{background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff!important}.pdp-btn-overlay-pitch:hover{background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff!important}
    .pdp-btn-overlay-contact{background:linear-gradient(135deg,#10b981,#059669);color:#fff!important}.pdp-btn-overlay-contact:hover{background:linear-gradient(135deg,#059669,#047857);color:#fff!important}
    .pdp-overlay-divider{width:80%;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);margin:8px 0}
    .pdp-share-overlay-title{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.8);font-size:.85rem;font-weight:600}.pdp-share-overlay-title svg{width:18px;height:18px;color:#d4a853}
    .pdp-share-buttons-row{display:flex;gap:8px;flex-wrap:wrap;justify-content:center}
    .pdp-share-btn-overlay{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;border-radius:25px;font-weight:600;font-size:.78rem;text-decoration:none;cursor:pointer;border:none;transition:all .3s ease;color:#fff!important}.pdp-share-btn-overlay svg{width:16px;height:16px}.pdp-share-btn-overlay:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.25)}
    .pdp-share-whatsapp{background:linear-gradient(135deg,#25D366,#128C7E)}.pdp-share-whatsapp:hover{background:linear-gradient(135deg,#128C7E,#075E54)}
    .pdp-share-x{background:#000;border:1px solid #333}.pdp-share-x:hover{background:#14171A;border-color:#555}
    .pdp-share-linkedin{background:linear-gradient(135deg,#0A66C2,#004182)}.pdp-share-linkedin:hover{background:linear-gradient(135deg,#004182,#00294d)}
    .pdp-share-copy{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3)}.pdp-share-copy:hover{background:rgba(255,255,255,.25)}.pdp-share-copy.copied{background:linear-gradient(135deg,#10b981,#059669)!important;border-color:#10b981!important}
    .pdp-share-overlay .pdp-main-actions,.pdp-share-overlay .pdp-overlay-divider,.pdp-share-overlay .pdp-share-overlay-title,.pdp-share-overlay .pdp-share-buttons-row{transform:translateY(15px);opacity:0;transition:all .4s ease}
    .pdp-project-card:hover .pdp-share-overlay .pdp-main-actions,.pdp-project-card.show-share .pdp-share-overlay .pdp-main-actions{transform:translateY(0);opacity:1;transition-delay:.1s}
    .pdp-project-card:hover .pdp-share-overlay .pdp-overlay-divider,.pdp-project-card.show-share .pdp-share-overlay .pdp-overlay-divider{transform:translateY(0);opacity:1;transition-delay:.2s}
    .pdp-project-card:hover .pdp-share-overlay .pdp-share-overlay-title,.pdp-project-card.show-share .pdp-share-overlay .pdp-share-overlay-title{transform:translateY(0);opacity:1;transition-delay:.25s}
    .pdp-project-card:hover .pdp-share-overlay .pdp-share-buttons-row,.pdp-project-card.show-share .pdp-share-overlay .pdp-share-buttons-row{transform:translateY(0);opacity:1;transition-delay:.3s}
    @media(max-width:768px){.pdp-share-overlay{opacity:0;visibility:hidden;padding:15px 12px;gap:10px}.pdp-project-card.show-share .pdp-share-overlay{opacity:1;visibility:visible}.pdp-share-close-mobile{display:flex}.pdp-main-actions{max-width:100%;gap:8px}.pdp-overlay-btn-main{padding:12px 20px;font-size:.88rem;border-radius:10px}.pdp-overlay-btn-main svg{width:20px;height:20px}.pdp-share-buttons-row{gap:6px}.pdp-share-btn-overlay{padding:8px 12px;font-size:.72rem}.pdp-share-btn-overlay svg{width:14px;height:14px}.pdp-share-btn-overlay span{display:none}.pdp-share-overlay-title{font-size:.78rem}}
    @media(max-width:380px){.pdp-overlay-btn-main{padding:10px 16px;font-size:.82rem}}
    </style>
    <script>
    (function(){'use strict';var isEnglish=window.location.href.indexOf('pb-eng')!==-1||window.location.href.indexOf('lang=en')!==-1||document.documentElement.lang==='en';
    var texts=isEnglish?{viewDetails:'Project Details',pitchDeck:'Download Pitch Deck',contactFounder:'Contact Founder',shareTitle:'Share Project',whatsapp:'WhatsApp',twitter:'X',linkedin:'LinkedIn',copy:'Copy',copied:'Copied!'}:{viewDetails:'تفاصيل المشروع',pitchDeck:'تحميل Pitch Deck',contactFounder:'تواصل مع المؤسس',shareTitle:'مشاركة المشروع',whatsapp:'واتساب',twitter:'X',linkedin:'لينكدإن',copy:'نسخ',copied:'تم!'};
    function escapeAttr(str){if(!str)return'';return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}
    function addShareOverlays(){document.querySelectorAll('.pdp-project-card').forEach(function(card){if(card.querySelector('.pdp-share-overlay'))return;var titleEl=card.querySelector('.pdp-card-title'),detailsBtn=card.querySelector('.pdp-btn-details'),pitchBtn=card.querySelector('.pdp-btn-pitch'),contactBtn=card.querySelector('.pdp-btn-contact');if(!titleEl||!detailsBtn)return;var projectTitle=titleEl.textContent.trim(),projectUrl=detailsBtn.getAttribute('href'),hasPitch=!!pitchBtn,hasContact=!!contactBtn;
    var shareUrl=projectUrl;if(isEnglish&&shareUrl.indexOf('lang=en')===-1)shareUrl+=(shareUrl.indexOf('?')!==-1?'&':'?')+'lang=en';
    var encodedTitle=encodeURIComponent(projectTitle),encodedUrl=encodeURIComponent(shareUrl);
    var h='<div class="pdp-share-overlay"><button type="button" class="pdp-share-close-mobile" onclick="event.stopPropagation();this.closest(\'.pdp-project-card\').classList.remove(\'show-share\');">×</button><div class="pdp-main-actions">';
    h+='<a href="'+projectUrl+'" class="pdp-overlay-btn-main pdp-btn-overlay-details"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span>'+texts.viewDetails+'</span></a>';
    if(hasPitch){h+='<button type="button" class="pdp-overlay-btn-main pdp-btn-overlay-pitch" data-project="'+escapeAttr(pitchBtn.getAttribute('data-project')||projectTitle)+'" data-pitch="'+escapeAttr(pitchBtn.getAttribute('data-pitch')||'')+'" data-founder="'+escapeAttr(pitchBtn.getAttribute('data-founder')||'')+'" data-whatsapp="'+escapeAttr(pitchBtn.getAttribute('data-whatsapp')||'')+'" onclick="event.stopPropagation();pdpOverlayPitch(this);"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span>'+texts.pitchDeck+'</span></button>'}
    if(hasContact){h+='<button type="button" class="pdp-overlay-btn-main pdp-btn-overlay-contact" data-project="'+escapeAttr(contactBtn.getAttribute('data-project')||projectTitle)+'" data-founder="'+escapeAttr(contactBtn.getAttribute('data-founder')||'')+'" data-whatsapp="'+escapeAttr(contactBtn.getAttribute('data-whatsapp')||'')+'" onclick="event.stopPropagation();pdpOverlayContact(this);"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><span>'+texts.contactFounder+'</span></button>'}
    h+='</div><div class="pdp-overlay-divider"></div><div class="pdp-share-overlay-title"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg><span>'+texts.shareTitle+'</span></div>';
    h+='<div class="pdp-share-buttons-row"><a href="https://wa.me/?text='+encodedTitle+'%20'+encodedUrl+'" target="_blank" class="pdp-share-btn-overlay pdp-share-whatsapp" onclick="event.stopPropagation();"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg><span>'+texts.whatsapp+'</span></a>';
    h+='<a href="https://twitter.com/intent/tweet?url='+encodedUrl+'&text='+encodedTitle+'" target="_blank" class="pdp-share-btn-overlay pdp-share-x" onclick="event.stopPropagation();"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg><span>'+texts.twitter+'</span></a>';
    h+='<a href="https://www.linkedin.com/sharing/share-offsite/?url='+encodedUrl+'" target="_blank" class="pdp-share-btn-overlay pdp-share-linkedin" onclick="event.stopPropagation();"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg><span>'+texts.linkedin+'</span></a>';
    h+='<button type="button" class="pdp-share-btn-overlay pdp-share-copy" onclick="event.stopPropagation();pdpCopyShareLink(this,\''+shareUrl.replace(/'/g,"\\'")+'\');"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg><span>'+texts.copy+'</span></button></div></div>';
    card.insertAdjacentHTML('beforeend',h)})}
    window.pdpCopyShareLink=function(btn,url){navigator.clipboard.writeText(url).then(function(){btn.classList.add('copied');var s=btn.querySelector('span');if(s)s.textContent=texts.copied;setTimeout(function(){btn.classList.remove('copied');if(s)s.textContent=texts.copy},2000)})};
    window.pdpOverlayPitch=function(btn){var lang=isEnglish?'en':'ar',fn='pdpOpenPitchModal'+lang;if(typeof window[fn]==='function')window[fn](btn)};
    window.pdpOverlayContact=function(btn){var lang=isEnglish?'en':'ar',fn='pdpOpenContactModal'+lang;if(typeof window[fn]==='function')window[fn](btn)};
    function setupMobile(){if(window.innerWidth<=768){document.querySelectorAll('.pdp-project-card').forEach(function(card){card.removeEventListener('click',handleCardClick);card.addEventListener('click',handleCardClick)})}}
    function handleCardClick(e){if(e.target.closest('.pdp-share-overlay a,.pdp-share-overlay button'))return;if(this.classList.contains('show-share'))this.classList.remove('show-share');else{document.querySelectorAll('.pdp-project-card').forEach(function(c){c.classList.remove('show-share')});this.classList.add('show-share')}}
    function init(){setTimeout(addShareOverlays,200);setTimeout(setupMobile,300)}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
    if(typeof jQuery!=='undefined')jQuery(document).ajaxComplete(function(){setTimeout(addShareOverlays,300);setTimeout(setupMobile,400)});
    window.addEventListener('resize',setupMobile)})();
    </script>
    <?php
}

// ========== 30. Open Graph للمشاريع ==========
add_filter('rank_math/opengraph/facebook/image', 'pdp_force_project_og_image', 99);
add_filter('rank_math/opengraph/twitter/image', 'pdp_force_project_og_image', 99);
function pdp_force_project_og_image($image) {
    if (!is_singular('project')) return $image;
    $pid = get_the_ID();
    if (has_post_thumbnail($pid)) return get_the_post_thumbnail_url($pid, 'full');
    $gallery = get_post_meta($pid, 'project_gallery', true);
    if ($gallery) {
        $ids = is_array($gallery) ? $gallery : explode(',', $gallery);
        if (!empty($ids[0])) return wp_get_attachment_image_url(intval($ids[0]), 'full');
    }
    return $image;
}

add_filter('rank_math/opengraph/facebook/og_title', 'pdp_en_og_title', 99);
add_filter('rank_math/opengraph/twitter/twitter_title', 'pdp_en_og_title', 99);
function pdp_en_og_title($title) {
    if (!is_singular('project') || !isset($_GET['lang']) || $_GET['lang'] !== 'en') return $title;
    $en = get_post_meta(get_the_ID(), 'title_en', true);
    return $en ? $en : $title;
}

add_filter('rank_math/opengraph/facebook/og_description', 'pdp_en_og_desc', 99);
add_filter('rank_math/opengraph/twitter/twitter_description', 'pdp_en_og_desc', 99);
function pdp_en_og_desc($desc) {
    if (!is_singular('project') || !isset($_GET['lang']) || $_GET['lang'] !== 'en') return $desc;
    $en = get_post_meta(get_the_ID(), 'excerpt_en', true);
    if (!$en) $en = get_post_meta(get_the_ID(), 'project_details_en', true);
    if ($en) { $en = wp_strip_all_tags($en); if (strlen($en) > 150) $en = substr($en, 0, 147) . '...'; return $en; }
    return $desc;
}









// ========== 32. WhatsApp/Social Bots - Direct Response (الحل النهائي) ==========

/*
 * المشكلة الحقيقية: واتساب وفيسبوك crawlers مش قادرين يوصلوا
 * لصفحات المشاريع لأن Cloudflare أو بلجن حماية بيمنعهم.
 * 
 * الحل: نكتشف الـ bot في أقرب وقت ممكن (قبل أي بلجن تاني)
 * ونرد عليه بصفحة HTML خفيفة فيها OG tags بس.
 */

// لازم يشتغل في أبكر وقت ممكن - قبل أي بلجن حماية
add_action('init', 'pdp_serve_social_bots', 1);
function pdp_serve_social_bots() {
    // نشيك لو ده bot من سوشيال ميديا
    if (!pdp_is_social_bot()) return;

    // نشيك لو ده صفحة مشروع
    $url = $_SERVER['REQUEST_URI'];

    // نطابق مسار المشاريع /projects/SLUG/ أو /projects/SLUG
    if (!preg_match('#^/projects/([^/\?]+)#i', $url, $matches)) return;

    $slug = sanitize_title($matches[1]);
    if (empty($slug)) return;

    // نجيب المشروع من الـ slug
    $posts = get_posts([
        'post_type'      => 'project',
        'name'           => $slug,
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    ]);

    if (empty($posts)) return;

    $post = $posts[0];
    $pid = $post->ID;

    // نحدد اللغة
    $lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';
    $d = pdp_get_project_data($pid, $lang);

    // العنوان
    $title = wp_strip_all_tags($d['title']);

    // الوصف (قصير)
    $desc = '';
    if (!empty($d['excerpt'])) $desc = wp_strip_all_tags($d['excerpt']);
    elseif (!empty($d['project_details'])) $desc = wp_strip_all_tags($d['project_details']);
    $desc = mb_substr(trim(preg_replace('/\s+/', ' ', $desc)), 0, 150);
    if (empty($desc)) $desc = $title;

    // الرابط
    $permalink = get_permalink($pid);
    if ($lang === 'en') $permalink = add_query_arg('lang', 'en', $permalink);
    $url = esc_url($permalink);

    // الصورة
    $img = '';
    if (has_post_thumbnail($pid)) {
        $img = get_the_post_thumbnail_url($pid, 'large');
    } else {
        $gallery = get_post_meta($pid, 'project_gallery', true);
        if ($gallery) {
            $ids = array_filter(explode(',', $gallery));
            if (!empty($ids[0])) {
                $img = wp_get_attachment_image_url(intval($ids[0]), 'large');
            }
        }
    }

    // نحاول نستخدم الصورة المضغوطة لو موجودة
    $tiny = pdp_get_tiny_og_image($pid);
    if ($tiny) $img = $tiny;

    // نتأكد إن الرابط https
    if ($img) $img = set_url_scheme($img, 'https');

    $site = ($lang === 'en') ? 'Projects Bank - Vibes Holding' : 'بنك المشاريع - فايبز القابضة';

    // نبعت الـ response مباشرة - صفحة HTML خفيفة جداً
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Robots-Tag: noindex');

    echo '<!DOCTYPE html><html><head>';
    echo '<meta charset="UTF-8">';
    echo '<title>' . esc_html($title) . '</title>';
    echo '<meta property="og:type" content="article"/>';
    echo '<meta property="og:site_name" content="' . esc_attr($site) . '"/>';
    echo '<meta property="og:title" content="' . esc_attr($title) . '"/>';
    echo '<meta property="og:description" content="' . esc_attr($desc) . '"/>';
    echo '<meta property="og:url" content="' . $url . '"/>';
    if ($img) {
        echo '<meta property="og:image" content="' . esc_url($img) . '"/>';
        echo '<meta property="og:image:type" content="image/jpeg"/>';
        echo '<meta property="og:image:width" content="600"/>';
        echo '<meta property="og:image:height" content="315"/>';
    }
    echo '<meta name="twitter:card" content="summary_large_image"/>';
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '"/>';
    echo '<meta name="twitter:description" content="' . esc_attr($desc) . '"/>';
    if ($img) echo '<meta name="twitter:image" content="' . esc_url($img) . '"/>';
    // لو مستخدم عادي فتح الصفحة - redirect للصفحة الحقيقية
    echo '<meta http-equiv="refresh" content="0;url=' . $url . '"/>';
    echo '</head><body>';
    echo '<p><a href="' . $url . '">' . esc_html($title) . '</a></p>';
    echo '</body></html>';
    exit;
}

// دالة كشف bots السوشيال ميديا
function pdp_is_social_bot() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    $bots = [
        'whatsapp',        // WhatsApp
        'facebookexternal', // Facebook
        'facebot',         // Facebook
        'telegrambot',     // Telegram
        'twitterbot',      // Twitter/X
        'linkedinbot',     // LinkedIn
        'slackbot',        // Slack
        'discordbot',      // Discord
        'skypeuripreview',  // Skype
        'vkshare',         // VK
        'pinterestbot',    // Pinterest
        'embedly',         // Embedly
        'quora link',      // Quora
        'showyoubot',      // ShowYou
        'outbrain',        // Outbrain
        'rogerbot',        // SEOmoz
        'developers.google', // Google
    ];

    foreach ($bots as $bot) {
        if (strpos($ua, $bot) !== false) return true;
    }

    return false;
}

// دالة إنشاء صورة OG مضغوطة (اختياري - لو مش موجودة بيستخدم الأصلية)
function pdp_get_tiny_og_image($pid) {
    $up = wp_upload_dir();
    $dir = $up['basedir'] . '/pb-og/';

    // نشوف لو فيه صورة جاهزة
    $existing = glob($dir . 'og' . $pid . '*.jpg');
    if (!empty($existing) && filesize($existing[0]) < 500000) {
        return $up['baseurl'] . '/pb-og/' . basename($existing[0]);
    }

    // نعمل صورة جديدة
    $att_id = 0;
    if (has_post_thumbnail($pid)) {
        $att_id = get_post_thumbnail_id($pid);
    } else {
        $g = get_post_meta($pid, 'project_gallery', true);
        if ($g) {
            $ids = array_filter(explode(',', $g));
            if (!empty($ids[0])) $att_id = intval($ids[0]);
        }
    }
    if (!$att_id) return '';

    $src = get_attached_file($att_id);
    if (!$src || !file_exists($src)) return '';

    if (!is_dir($dir)) wp_mkdir_p($dir);

    $fname = 'og' . $pid . '_' . md5($att_id) . '.jpg';
    $path = $dir . $fname;
    $url = $up['baseurl'] . '/pb-og/' . $fname;

    if (file_exists($path) && filesize($path) < 500000) {
        return set_url_scheme($url, 'https');
    }

    $ed = wp_get_image_editor($src);
    if (is_wp_error($ed)) return '';

    $sz = $ed->get_size();
    $r = 600 / 315;
    $ow = $sz['width'];
    $oh = $sz['height'];

    if (($ow / $oh) > $r) {
        $cw = round($oh * $r);
        $ch = $oh;
        $cx = round(($ow - $cw) / 2);
        $cy = 0;
    } else {
        $cw = $ow;
        $ch = round($ow / $r);
        $cx = 0;
        $cy = round(($oh - $ch) / 2);
    }

    $ed->crop($cx, $cy, $cw, $ch);
    $ed->resize(600, 315, false);
    $ed->set_quality(55);
    $saved = $ed->save($path, 'image/jpeg');

    if (is_wp_error($saved)) return '';

    // لو لسه كبيرة
    if (filesize($path) > 400000) {
        $ed2 = wp_get_image_editor($path);
        if (!is_wp_error($ed2)) {
            $ed2->resize(400, 210, false);
            $ed2->set_quality(40);
            $ed2->save($path, 'image/jpeg');
        }
    }

    return set_url_scheme($url, 'https');
}

// تنظيف عند حفظ المشروع
add_action('save_post_project', 'pdp_clear_og_images', 200);
function pdp_clear_og_images($pid) {
    $dir = wp_upload_dir()['basedir'] . '/pb-og/';
    if (!is_dir($dir)) return;
    foreach (glob($dir . 'og' . $pid . '*.jpg') as $f) @unlink($f);
}






// ========== 33. إصلاح فيديو الموبايل (الحل النهائي) ==========

// الطريقة: بدل ما نحط iframe مباشرة، نحط thumbnail + زر play
// ولما المستخدم يضغط play نحمّل الفيديو - ده بيحل كل مشاكل الكاش والـ lazy loading

add_action('wp_head', 'pdp_fix_mobile_video_final', 999);
function pdp_fix_mobile_video_final() {
    ?>
    <style>
    /* === حل الفيديو النهائي === */
    .pdp-hero-video {
        position: relative !important;
        width: 100% !important;
        padding-bottom: 45% !important;
        height: 0 !important;
        overflow: hidden !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        background: #000 !important;
    }

    .pdp-hero-video iframe {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        border: 0 !important;
        z-index: 2 !important;
    }

    /* Thumbnail + Play Button (الحل البديل) */
    .pdp-video-thumb-wrap {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        z-index: 5 !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #000 !important;
    }

    .pdp-video-thumb-wrap img {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    .pdp-video-play-btn {
        position: relative !important;
        z-index: 6 !important;
        width: 72px !important;
        height: 72px !important;
        background: rgba(255, 0, 0, 0.85) !important;
        border-radius: 50% !important;
        border: 4px solid #fff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: transform 0.3s ease, background 0.3s ease !important;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.5) !important;
    }

    .pdp-video-play-btn::after {
        content: '' !important;
        display: block !important;
        width: 0 !important;
        height: 0 !important;
        border-style: solid !important;
        border-width: 14px 0 14px 26px !important;
        border-color: transparent transparent transparent #fff !important;
        margin-left: 5px !important;
    }

    .pdp-video-thumb-wrap:hover .pdp-video-play-btn {
        transform: scale(1.12) !important;
        background: rgba(255, 0, 0, 1) !important;
    }

    .pdp-video-thumb-wrap.pdp-playing {
        display: none !important;
    }

    @media (max-width: 768px) {
        .pdp-hero-video {
            padding-bottom: 56.25% !important;
            min-height: unset !important;
        }

        .pdp-video-play-btn {
            width: 60px !important;
            height: 60px !important;
        }

        .pdp-video-play-btn::after {
            border-width: 11px 0 11px 20px !important;
            margin-left: 4px !important;
        }
    }

    @media (max-width: 400px) {
        .pdp-video-play-btn {
            width: 50px !important;
            height: 50px !important;
            border-width: 3px !important;
        }

        .pdp-video-play-btn::after {
            border-width: 9px 0 9px 16px !important;
            margin-left: 3px !important;
        }
    }
    </style>
    <?php
}

add_action('wp_footer', 'pdp_fix_lazy_video_final', 999);
function pdp_fix_lazy_video_final() {
    ?>
    <script>
    (function(){
        'use strict';

        var VIDEO_ID = 'xsoLckayQZA';
        var container = document.querySelector('.pdp-hero-video');
        if (!container) return;

        function initVideoFix() {
            // === الخطوة 1: نشيل أي iframe موجود (ممكن يكون معطل من الكاش) ===
            var existingIframe = container.querySelector('iframe');

            // === الخطوة 2: نشيك لو الـ iframe شغال فعلاً ===
            var iframeWorking = false;
            if (existingIframe) {
                var src = existingIframe.getAttribute('src') || '';
                var dataSrc = existingIframe.getAttribute('data-src') || existingIframe.getAttribute('data-lazy-src') || '';

                // لو الـ src فاضي أو about:blank يبقى مش شغال
                if (src && src !== 'about:blank' && src.indexOf('youtube') !== -1) {
                    iframeWorking = true;
                }
                // لو فيه data-src يبقى بلجن كاش غيّره
                else if (dataSrc && dataSrc.indexOf('youtube') !== -1) {
                    // نحاول نرجع الـ src
                    existingIframe.setAttribute('src', dataSrc);
                    existingIframe.removeAttribute('data-src');
                    existingIframe.removeAttribute('data-lazy-src');
                    existingIframe.setAttribute('loading', 'eager');
                    iframeWorking = true;
                }
            }

            // === الخطوة 3: لو الـ iframe مش شغال - نعمل Thumbnail + Play ===
            // ده الحل المضمون 100% - مفيش بلجن كاش هيقدر يعطله
            if (!iframeWorking || isMobile()) {
                // نشيل الـ iframe القديم
                if (existingIframe) {
                    existingIframe.style.display = 'none';
                }

                // نشيك لو الـ thumbnail موجود بالفعل
                if (container.querySelector('.pdp-video-thumb-wrap')) return;

                // YouTube thumbnail - بنجرب أعلى جودة الأول
                var thumbUrl = 'https://img.youtube.com/vi/' + VIDEO_ID + '/maxresdefault.jpg';

                var thumbWrap = document.createElement('div');
                thumbWrap.className = 'pdp-video-thumb-wrap';
                thumbWrap.innerHTML = '<img src="' + thumbUrl + '" alt="Play Video" onerror="this.src=\'https://img.youtube.com/vi/' + VIDEO_ID + '/hqdefault.jpg\'">'
                    + '<div class="pdp-video-play-btn"></div>';

                thumbWrap.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // نعمل iframe جديد تماماً
                    var newIframe = document.createElement('iframe');
                    newIframe.setAttribute('src', 'https://www.youtube.com/embed/' + VIDEO_ID + '?rel=0&modestbranding=1&playsinline=1&autoplay=1');
                    newIframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                    newIframe.setAttribute('allowfullscreen', '');
                    newIframe.setAttribute('loading', 'eager');
                    newIframe.setAttribute('title', 'Projects Bank Video');
                    newIframe.style.cssText = 'position:absolute!important;top:0!important;left:0!important;width:100%!important;height:100%!important;border:0!important;display:block!important;visibility:visible!important;opacity:1!important;z-index:10!important;';

                    // نشيل الـ iframe القديم لو موجود
                    var oldIframe = container.querySelector('iframe');
                    if (oldIframe) oldIframe.remove();

                    // نضيف الجديد
                    container.appendChild(newIframe);

                    // نخبي الـ thumbnail
                    thumbWrap.classList.add('pdp-playing');
                });

                container.appendChild(thumbWrap);
            }
        }

        function isMobile() {
            return window.innerWidth <= 768
                || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // === ننفذ في أقرب وقت ===
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initVideoFix, 50);
            });
        } else {
            setTimeout(initVideoFix, 50);
        }

        // === Safety net: ننفذ تاني بعد ثانيتين لو بلجن كاش غيّر حاجة متأخر ===
        setTimeout(initVideoFix, 2000);
        setTimeout(initVideoFix, 4000);
    })();
    </script>
    <?php
}







// ========== 34. إجمالي مشاهدات المشاريع في البداية ==========
add_action('wp_footer', 'pdp_total_views_counter', 998);
function pdp_total_views_counter() {
    global $post;
    if (!$post || (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en'))) return;

    $is_english = has_shortcode($post->post_content, 'projects_directory_en');

    // جلب إجمالي المشاهدات مع كاش
    $total_views = get_transient('pdp_total_views');
    if ($total_views === false) {
        global $wpdb;
        $total_views = (int) $wpdb->get_var(
            "SELECT COALESCE(SUM(CAST(pm.meta_value AS UNSIGNED)), 0)
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = 'views_count'
             AND p.post_type = 'project'
             AND p.post_status = 'publish'"
        );
        set_transient('pdp_total_views', $total_views, HOUR_IN_SECONDS);
    }

    $label = $is_english ? 'Total Project Views' : 'إجمالي مشاهدات المشاريع';
    ?>
    <style>
    .pdp-total-views-bar{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:12px;
        padding:14px 25px;
        margin-bottom:15px;
        background:linear-gradient(135deg,#1D2D51 0%,#2a4a7f 100%);
        border-radius:14px;
        box-shadow:0 4px 20px rgba(29,45,81,.2);
        border:1px solid rgba(212,168,83,.2);
    }
    .pdp-total-views-bar svg{
        width:24px;
        height:24px;
        color:#d4a853;
        flex-shrink:0;
    }
    .pdp-total-views-num{
        font-size:1.6rem;
        font-weight:900;
        color:#d4a853;
        line-height:1;
        font-family:'Cairo','Inter',sans-serif;
    }
    .pdp-total-views-label{
        font-size:.88rem;
        font-weight:700;
        color:rgba(255,255,255,.85);
        font-family:'Cairo','Inter',sans-serif;
    }
    @media(max-width:768px){
        .pdp-total-views-bar{
            padding:12px 18px;
            gap:10px;
            border-radius:10px;
        }
        .pdp-total-views-num{font-size:1.3rem}
        .pdp-total-views-label{font-size:.8rem}
        .pdp-total-views-bar svg{width:20px;height:20px}
    }
    </style>
    <script>
    (function(){
        var total = <?php echo (int) $total_views; ?>;
        var label = '<?php echo esc_js($label); ?>';

        function insert(){
            var hero = document.querySelector('.pdp-hero-section');
            if (!hero) return false;
            if (document.getElementById('pdpTotalViewsBar')) return true;

            var bar = document.createElement('div');
            bar.className = 'pdp-total-views-bar';
            bar.id = 'pdpTotalViewsBar';
            bar.innerHTML = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                + '<span class="pdp-total-views-num" id="pdpTotalViewsNum">0</span>'
                + '<span class="pdp-total-views-label">' + label + '</span>';

            hero.parentNode.insertBefore(bar, hero.nextSibling);

            // أنيميشن العداد
            var el = document.getElementById('pdpTotalViewsNum');
            if (el && total > 0) {
                var duration = 2000;
                var start = null;
                function ease(t){ return 1 - Math.pow(1 - t, 4); }
                function animate(ts){
                    if (!start) start = ts;
                    var prog = Math.min((ts - start) / duration, 1);
                    el.textContent = Math.floor(ease(prog) * total).toLocaleString();
                    if (prog < 1) requestAnimationFrame(animate);
                    else el.textContent = total.toLocaleString();
                }
                setTimeout(function(){ requestAnimationFrame(animate); }, 600);
            }
            return true;
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ setTimeout(insert, 150); });
        } else {
            setTimeout(insert, 150);
        }
    })();
    </script>
    <?php
}




// ========== 36. ضغط صور المشاريع - Image Optimizer ==========

/**
 * دالة واحدة بسيطة: تجيب أحسن حجم صورة حسب السياق
 * بدل ما نحمل large (1024px) على الموبايل - نحمل medium (300px) أو medium_large (768px)
 */
function pdp_get_optimized_image_url($attachment_id, $context = 'gallery') {
    if (!$attachment_id) return '';

    // نكتشف الموبايل من السيرفر
    $is_mobile = pdp_is_mobile_device();

    switch ($context) {
        case 'thumbnail': // الصور الصغيرة
            $size = 'thumbnail'; // 150x150
            break;

        case 'card': // كروت المشاريع في الصفحة الرئيسية
            $size = $is_mobile ? 'medium' : 'medium_large';
            break;

        case 'gallery': // جاليري صفحة المشروع المفردة
            $size = $is_mobile ? 'medium_large' : 'large';
            break;

        case 'lightbox': // عند الضغط لتكبير الصورة
            $size = 'large';
            break;

        default:
            $size = $is_mobile ? 'medium_large' : 'large';
    }

    $url = wp_get_attachment_image_url($attachment_id, $size);

    // لو الحجم المطلوب مش موجود - نرجع للأصلي
    if (!$url) {
        $url = wp_get_attachment_image_url($attachment_id, 'large');
    }

    return $url ?: '';
}

/**
 * كشف الموبايل من السيرفر
 */
function pdp_is_mobile_device() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;
    $ua = $_SERVER['HTTP_USER_AGENT'];
    return (bool) preg_match('/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|webOS/i', $ua);
}

/**
 * دالة محسّنة لجلب صور المشروع - تستبدل الطريقة القديمة
 */
function pdp_get_project_images($pid, $context = 'gallery') {
    $images = [];
    $large_images = []; // للـ lightbox

    if (has_post_thumbnail($pid)) {
        $thumb_id = get_post_thumbnail_id($pid);
        $images[] = pdp_get_optimized_image_url($thumb_id, $context);
        $large_images[] = wp_get_attachment_image_url($thumb_id, 'large');
    }

    $gallery = get_post_meta($pid, 'project_gallery', true);
    if ($gallery) {
        $ids = array_filter(explode(',', $gallery));
        foreach ($ids as $id) {
            $url = pdp_get_optimized_image_url($id, $context);
            $large_url = wp_get_attachment_image_url($id, 'large');
            if ($url && !in_array($url, $images)) {
                $images[] = $url;
                $large_images[] = $large_url ?: $url;
            }
        }
    }

    if (empty($images)) {
        $title = get_the_title($pid);
        $placeholder = 'https://via.placeholder.com/600x600/1D2D51/C9A961?text=' . urlencode($title);
        $images[] = $placeholder;
        $large_images[] = $placeholder;
    }

    return [
        'display' => $images,     // للعرض العادي (محسّنة)
        'full'    => $large_images // للـ lightbox (كاملة)
    ];
}

/**
 * إضافة srcset للصور في صفحة المشروع المفردة
 * ده بيخلي المتصفح يختار أحسن حجم تلقائي
 */
function pdp_get_image_srcset($attachment_id) {
    if (!$attachment_id) return '';

    $srcset = wp_get_attachment_image_srcset($attachment_id, 'large');
    $sizes = '(max-width: 400px) 400px, (max-width: 768px) 768px, 900px';

    if ($srcset) {
        return 'srcset="' . esc_attr($srcset) . '" sizes="' . esc_attr($sizes) . '"';
    }

    return '';
}

/**
 * أوامر لـ FlyingPress عشان يشتغل صح مع الصور
 */
add_action('wp_head', 'pdp_flyingpress_hints', 5);
function pdp_flyingpress_hints() {
    if (!is_singular('project')) return;

    $pid = get_the_ID();
    if (!$pid) return;

    // نعمل preload للصورة الأولى بس
    $first_image = '';
    if (has_post_thumbnail($pid)) {
        $size = pdp_is_mobile_device() ? 'medium_large' : 'large';
        $first_image = get_the_post_thumbnail_url($pid, $size);
    } else {
        $gallery = get_post_meta($pid, 'project_gallery', true);
        if ($gallery) {
            $ids = array_filter(explode(',', $gallery));
            if (!empty($ids[0])) {
                $size = pdp_is_mobile_device() ? 'medium_large' : 'large';
                $first_image = wp_get_attachment_image_url(intval($ids[0]), $size);
            }
        }
    }

    if ($first_image) {
        echo '<link rel="preload" as="image" href="' . esc_url($first_image) . '">' . "\n";
    }
}


















// ========== 37. ترتيب "اكتشف" - Discover Shuffle (متوافق مع FlyingPress) ==========

add_action('wp_footer', 'pdp_discover_shuffle_script', 997);
function pdp_discover_shuffle_script() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;

    $is_english = has_shortcode($post->post_content, 'projects_directory_en');
    ?>
    <style>
    @keyframes pdpShuffleOut {
        0%   { opacity: 1; transform: scale(1) translateY(0); }
        100% { opacity: 0; transform: scale(0.85) translateY(20px); }
    }
    @keyframes pdpShuffleIn {
        0%   { opacity: 0; transform: scale(0.85) translateY(20px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    .pdp-project-card.pdp-shuffling-out {
        animation: pdpShuffleOut 0.25s ease forwards !important;
    }
    .pdp-project-card.pdp-shuffling-in {
        animation: pdpShuffleIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards !important;
    }
    .pdp-discover-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: #fff !important;
        border: none;
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    .pdp-discover-btn:hover {
        background: linear-gradient(135deg, #7c3aed, #5b21b6);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(109, 40, 217, 0.35);
        color: #fff !important;
    }
    .pdp-discover-btn:active { transform: translateY(0); }
    .pdp-discover-btn svg {
        width: 18px;
        height: 18px;
        transition: transform 0.4s ease;
    }
    .pdp-discover-btn:hover svg { transform: rotate(180deg); }
    .pdp-discover-btn.pdp-spinning svg {
        animation: pdpSpin 0.5s ease;
    }
    @keyframes pdpSpin {
        0%   { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @media (max-width: 768px) {
        .pdp-discover-btn {
            width: 100%;
            justify-content: center;
            padding: 12px 20px;
            font-size: 0.9rem;
        }
    }
    </style>

    <script>
    (function() {
        'use strict';

        var isEnglish = <?php echo $is_english ? 'true' : 'false'; ?>;
        var lang = isEnglish ? 'en' : 'ar';
        var texts = isEnglish
            ? { discover: 'Discover', discoverTooltip: 'Shuffle all projects randomly' }
            : { discover: 'اكتشف', discoverTooltip: 'ترتيب عشوائي لجميع المشاريع' };

        function addDiscoverOption() {
            var orderSelect = document.getElementById('pdpOrderFilter' + lang);
            if (!orderSelect || orderSelect.querySelector('option[value="discover"]')) return;
            var option = document.createElement('option');
            option.value = 'discover';
            option.textContent = '🔀 ' + texts.discover;
            orderSelect.appendChild(option);
            var url = new URL(window.location.href);
            if (url.searchParams.get('orderby') === 'discover') {
                option.selected = true;
                setTimeout(doShuffle, 300);
            }
        }

        function addDiscoverButton() {
            var filters = document.querySelector('.pdp-filters');
            if (!filters || document.getElementById('pdpDiscoverBtn')) return;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'pdpDiscoverBtn';
            btn.className = 'pdp-discover-btn';
            btn.title = texts.discoverTooltip;
            btn.innerHTML = '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">'
                + '<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'
                + '</svg><span>' + texts.discover + '</span>';
            btn.addEventListener('click', function() {
                this.classList.add('pdp-spinning');
                var self = this;
                setTimeout(function() { self.classList.remove('pdp-spinning'); }, 500);
                doShuffle();
            });
            filters.appendChild(btn);
        }

        // === الـ Shuffle الرئيسي - كل المشاريع بدون استثناء ===
        function doShuffle() {
            var grid = document.getElementById('pdpProjectsGrid' + lang);
            if (!grid) return;

            var allCards = Array.from(grid.querySelectorAll('.pdp-project-card'));
            if (allCards.length < 2) return;

            // نخلط الكل مع بعض - مميزة وعادية
            shuffleArray(allCards);

            // أنيميشن: إخفاء
            allCards.forEach(function(card) {
                card.classList.add('pdp-shuffling-out');
            });

            // بعد الإخفاء: نعيد الترتيب
            setTimeout(function() {
                allCards.forEach(function(card) {
                    grid.removeChild(card);
                });

                allCards.forEach(function(card, index) {
                    card.classList.remove('pdp-shuffling-out');
                    card.classList.add('pdp-shuffling-in');
                    card.style.animationDelay = (index * 0.05) + 's';
                    grid.appendChild(card);
                });

                setTimeout(function() {
                    allCards.forEach(function(card) {
                        card.classList.remove('pdp-shuffling-in');
                        card.style.animationDelay = '';
                    });
                }, 600 + (allCards.length * 50));

            }, 250);
        }

        // Fisher-Yates Shuffle
        function shuffleArray(arr) {
            for (var i = arr.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var temp = arr[i];
                arr[i] = arr[j];
                arr[j] = temp;
            }
            return arr;
        }

        function handleOrderFilter() {
            var orderSelect = document.getElementById('pdpOrderFilter' + lang);
            if (!orderSelect) return;
            orderSelect.addEventListener('change', function(e) {
                if (this.value === 'discover') {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    doShuffle();
                    var url = new URL(window.location.href);
                    url.searchParams.set('orderby', 'discover');
                    window.history.replaceState({}, '', url.toString());
                    return false;
                }
            }, true);
        }

        function init() {
            addDiscoverOption();
            addDiscoverButton();
            handleOrderFilter();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(init, 200); });
        } else {
            setTimeout(init, 200);
        }

        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(function() {
                setTimeout(function() { addDiscoverOption(); addDiscoverButton(); }, 300);
            });
        }
    })();
    </script>
    <?php
}















// ========== 38. إصلاح ترتيب "الكل" في فلتر المرحلة والقطاع ==========
add_action('wp_footer', 'pdp_fix_filters_all_first', 996);
function pdp_fix_filters_all_first() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;
    ?>
    <script>
    (function(){
        'use strict';
        function fixSelectOrder() {
            // نجيب كل الـ selects بتاعت الفلاتر (القطاع + المرحلة)
            var selects = document.querySelectorAll('[id^="pdpSectorFilter"], [id^="pdpStageFilter"]');
            selects.forEach(function(select) {
                if (!select) return;
                // نلاقي option اللي قيمتها فاضية (الكل)
                var allOption = select.querySelector('option[value=""]');
                if (!allOption) return;
                // لو مش أول حاجة - نحركها للأول
                if (select.firstChild !== allOption) {
                    select.insertBefore(allOption, select.firstChild);
                }
                // لو مفيش حاجة متختارة في الـ URL - نخلي "الكل" هي الـ default
                var url = new URL(window.location.href);
                var paramName = select.id.indexOf('Sector') !== -1 ? 'sector' : 'stage';
                if (!url.searchParams.has(paramName) || url.searchParams.get(paramName) === '') {
                    allOption.selected = true;
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(fixSelectOrder, 60); });
        } else {
            setTimeout(fixSelectOrder, 60);
        }
    })();
    </script>
    <?php
}





// ========== 39. إخفاء Polylang من صفحات بنك المشاريع ==========
add_action('wp_head', 'pdp_hide_polylang_on_pb', 99999);
function pdp_hide_polylang_on_pb() {
    $uri = $_SERVER['REQUEST_URI'];
    if (strpos($uri, '/pb') === false && strpos($uri, 'project') === false) return;
    echo '<style>
    #menu-item-742-en,
    .menu-item-742-en,
    .lang-item,
    .lang-item-en,
    .lang-item-ar,
    li[class*="lang-item"],
    a[hreflang="en-US"],
    a[hreflang="en"],
    [class*="polylang"],
    [class*="pll-"] {
        display: none !important;
    }
    </style>';
}












// ========== 40. شريط البحث - Search Bar (موحّد، عربي/إنجليزي) ==========
add_action('wp_footer', 'pdp_advanced_search_bar', 995);
function pdp_advanced_search_bar() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;

    $is_english = has_shortcode($post->post_content, 'projects_directory_en');
    ?>
    <style>
    .pdp-search-wrapper { max-width: 1400px; margin: 0 auto 18px; padding: 0 15px; direction: <?php echo $is_english ? 'ltr' : 'rtl'; ?>; }
    .pdp-search-bar { display: flex; align-items: center; gap: 0; background: var(--pdp-card-bg, #fff); border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,.08); border: 2px solid var(--pdp-border, #e2e8f0); overflow: hidden; transition: all 0.3s ease; position: relative; }
    .pdp-search-bar:focus-within { border-color: var(--pdp-gold, #d4a853); box-shadow: 0 6px 35px rgba(212,168,83,.2); transform: translateY(-2px); }
    .pdp-search-icon { display: flex; align-items: center; justify-content: center; width: 56px; height: 56px; flex-shrink: 0; color: var(--pdp-gold, #d4a853); background: linear-gradient(135deg, rgba(212,168,83,.08), rgba(212,168,83,.03)); }
    .pdp-search-icon svg { width: 24px; height: 24px; }
    .pdp-search-input { flex: 1; border: none !important; outline: none !important; padding: 16px 18px !important; font-size: 1rem !important; font-weight: 600 !important; color: var(--pdp-text, #1e293b) !important; background: transparent !important; font-family: <?php echo $is_english ? "'Inter','Segoe UI',sans-serif" : "'Cairo','Segoe UI',sans-serif"; ?> !important; direction: <?php echo $is_english ? 'ltr' : 'rtl'; ?> !important; text-align: <?php echo $is_english ? 'left' : 'right'; ?> !important; box-shadow: none !important; margin: 0 !important; height: auto !important; min-height: unset !important; max-width: 100% !important; line-height: 1.5 !important; }
    .pdp-search-input::placeholder { color: var(--pdp-text-light, #94a3b8) !important; font-weight: 500 !important; opacity: 1 !important; }
    .pdp-search-input:focus { border: none !important; outline: none !important; box-shadow: none !important; }
    .pdp-search-clear { display: none; align-items: center; justify-content: center; width: 36px; height: 36px; margin: 0 8px; border-radius: 50%; border: none; background: rgba(239,68,68,.1); color: #ef4444; cursor: pointer; font-size: 18px; font-weight: 700; transition: all 0.3s ease; flex-shrink: 0; padding: 0; line-height: 1; }
    .pdp-search-clear.show { display: flex; }
    .pdp-search-clear:hover { background: #ef4444; color: #fff !important; transform: scale(1.1); }
    .pdp-search-count { display: none; align-items: center; padding: 6px 14px; margin: 0 10px; background: linear-gradient(135deg, var(--pdp-gold, #d4a853), var(--pdp-gold-light, #f0d78c)); color: var(--pdp-primary-dark, #0f1829) !important; border-radius: 25px; font-size: 0.78rem; font-weight: 800; white-space: nowrap; flex-shrink: 0; }
    .pdp-search-count.show { display: flex; }
    .pdp-search-no-results { display: none; text-align: center; padding: 50px 25px; color: var(--pdp-text-light, #64748b); background: var(--pdp-card-bg, #fff); border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); margin-top: 15px; }
    .pdp-search-no-results.show { display: block; }
    .pdp-search-no-results-icon { font-size: 3rem; margin-bottom: 12px; animation: pdpFloat 3s ease-in-out infinite; }
    .pdp-search-no-results h3 { font-size: 1.15rem; color: var(--pdp-primary, #1D2D51); font-weight: 800; margin-bottom: 8px; }
    .pdp-search-no-results p { font-size: 0.9rem; color: var(--pdp-text-light, #64748b); margin: 0; }
    .pdp-search-highlight { background: linear-gradient(135deg, rgba(212,168,83,.3), rgba(212,168,83,.15)); color: var(--pdp-primary, #1D2D51); padding: 1px 4px; border-radius: 4px; font-weight: 800; }
    .pdp-project-card.pdp-search-hidden { display: none !important; }
    .pdp-project-card.pdp-search-visible { animation: pdpSearchShow 0.35s ease forwards; }
    @keyframes pdpSearchShow { 0% { opacity: 0; transform: scale(0.92); } 100% { opacity: 1; transform: scale(1); } }
    @media (max-width: 768px) {
        .pdp-search-wrapper { padding: 0 10px; margin-bottom: 14px; }
        .pdp-search-bar { border-radius: 12px; }
        .pdp-search-icon { width: 46px; height: 46px; }
        .pdp-search-icon svg { width: 20px; height: 20px; }
        .pdp-search-input { padding: 14px 12px !important; font-size: 0.92rem !important; }
        .pdp-search-count { padding: 5px 10px; font-size: 0.72rem; margin: 0 6px; }
        .pdp-search-clear { width: 32px; height: 32px; margin: 0 6px; font-size: 16px; }
    }
    @media (max-width: 400px) {
        .pdp-search-icon { width: 40px; height: 40px; }
        .pdp-search-input { padding: 12px 10px !important; font-size: 0.88rem !important; }
    }
    </style>

    <script>
    (function() {
        'use strict';

        var isEnglish = <?php echo $is_english ? 'true' : 'false'; ?>;
        var lang = isEnglish ? 'en' : 'ar';
        var texts = isEnglish
            ? { placeholder: 'Search projects...', noResultsTitle: 'No projects found', noResultsMsg: 'Try a different keyword or clear the search', resultCount: 'projects', resultOne: 'project' }
            : { placeholder: 'ابحث عن مشروع بالاسم أو القطاع...', noResultsTitle: 'لا توجد نتائج', noResultsMsg: 'جرّب كلمة مختلفة أو امسح البحث', resultCount: 'مشروع', resultOne: 'مشروع' };

        // === علم عالمي يمنع أي دالة بحث تانية من ربط listeners ===
        window.PDP_SEARCH_OWNED = true;

        // نبني نص البحث لكل كارت لو لسه مش موجود (احتياطي)
        function ensureSearchText(card) {
            var existing = card.getAttribute('data-search-text');
            if (existing && existing.length) return existing;
            var parts = [];
            var t = card.querySelector('.pdp-card-title'); if (t) parts.push(t.textContent);
            var s = card.querySelector('.pdp-tag-sector'); if (s) parts.push(s.textContent);
            var st = card.querySelector('.pdp-tag-stage'); if (st) parts.push(st.textContent);
            var ex = card.querySelector('.pdp-card-excerpt'); if (ex) parts.push(ex.textContent);
            var txt = parts.join(' ').toLowerCase();
            card.setAttribute('data-search-text', txt);
            return txt;
        }

        function init() {
            var filters = document.querySelector('.pdp-filters');
            var grid = document.getElementById('pdpProjectsGrid' + lang);
            if (!filters || !grid) return;
            if (document.getElementById('pdpSearchWrapper')) return;

            var wrapper = document.createElement('div');
            wrapper.className = 'pdp-search-wrapper';
            wrapper.id = 'pdpSearchWrapper';
            wrapper.innerHTML = '<div class="pdp-search-bar" id="pdpSearchBar">'
                + '<div class="pdp-search-icon"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></div>'
                + '<input type="text" class="pdp-search-input" id="pdpSearchInput" placeholder="' + texts.placeholder + '" autocomplete="off" spellcheck="false">'
                + '<span class="pdp-search-count" id="pdpSearchCount"></span>'
                + '<button type="button" class="pdp-search-clear" id="pdpSearchClear">×</button>'
                + '</div>';
            filters.parentNode.insertBefore(wrapper, filters);

            var noResults = document.createElement('div');
            noResults.className = 'pdp-search-no-results';
            noResults.id = 'pdpSearchNoResults';
            noResults.innerHTML = '<div class="pdp-search-no-results-icon">🔍</div><h3>' + texts.noResultsTitle + '</h3><p>' + texts.noResultsMsg + '</p>';
            grid.parentNode.insertBefore(noResults, grid.nextSibling);

            var input = document.getElementById('pdpSearchInput');
            var clearBtn = document.getElementById('pdpSearchClear');
            var countEl = document.getElementById('pdpSearchCount');
            var noResultsEl = document.getElementById('pdpSearchNoResults');

            // نجهّز نص البحث لكل الكروت مرة واحدة
            Array.prototype.forEach.call(grid.querySelectorAll('.pdp-project-card'), ensureSearchText);

            var debounceTimer;
            input.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() { doSearch(input.value.trim()); }, 180);
                if (input.value.trim().length > 0) clearBtn.classList.add('show');
                else clearBtn.classList.remove('show');
            });

            clearBtn.addEventListener('click', function() {
                input.value = '';
                clearBtn.classList.remove('show');
                doSearch('');
                input.focus();
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    input.value = '';
                    clearBtn.classList.remove('show');
                    doSearch('');
                    input.blur();
                }
            });

            function doSearch(query) {
                var cards = Array.prototype.slice.call(grid.querySelectorAll('.pdp-project-card'));
                var visibleCount = 0;
                var pag = document.querySelector('.pdp-pagination');

                if (!query) {
                    cards.forEach(function(card) {
                        card.classList.remove('pdp-search-hidden', 'pdp-search-visible');
                        removeHighlights(card);
                    });
                    countEl.classList.remove('show');
                    noResultsEl.classList.remove('show');
                    grid.style.display = '';
                    if (pag) pag.style.display = '';
                    return;
                }

                var queryWords = query.toLowerCase().split(/\s+/).filter(function(w) { return w.length > 0; });

                cards.forEach(function(card) {
                    var searchText = ensureSearchText(card);
                    var matches = queryWords.every(function(word) { return searchText.indexOf(word) !== -1; });
                    if (matches) {
                        card.classList.remove('pdp-search-hidden');
                        card.classList.add('pdp-search-visible');
                        highlightText(card, queryWords);
                        visibleCount++;
                    } else {
                        card.classList.add('pdp-search-hidden');
                        card.classList.remove('pdp-search-visible');
                        removeHighlights(card);
                    }
                });

                // أثناء البحث نخفي الـ pagination لأن البحث محلي على الصفحة الحالية
                if (pag) pag.style.display = 'none';

                if (visibleCount > 0) {
                    countEl.textContent = visibleCount + ' ' + (visibleCount === 1 ? texts.resultOne : texts.resultCount);
                    countEl.classList.add('show');
                    noResultsEl.classList.remove('show');
                    grid.style.display = '';
                } else {
                    countEl.classList.remove('show');
                    noResultsEl.classList.add('show');
                    grid.style.display = 'none';
                }
            }

            function highlightText(card, words) {
                removeHighlights(card);
                var els = card.querySelectorAll('.pdp-card-title, .pdp-tag-sector, .pdp-tag-stage');
                Array.prototype.forEach.call(els, function(el) {
                    var html = el.textContent;
                    words.forEach(function(word) {
                        if (word.length < 1) return;
                        var escaped = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        var regex = new RegExp('(' + escaped + ')', 'gi');
                        html = html.replace(regex, '<span class="pdp-search-highlight">$1</span>');
                    });
                    el.innerHTML = html;
                });
            }

            function removeHighlights(card) {
                var highlights = card.querySelectorAll('.pdp-search-highlight');
                Array.prototype.forEach.call(highlights, function(hl) {
                    var parent = hl.parentNode;
                    parent.replaceChild(document.createTextNode(hl.textContent), hl);
                    parent.normalize();
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(init, 250); });
        } else {
            setTimeout(init, 250);
        }
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(function() { setTimeout(init, 400); });
        }
    })();
    </script>
    <?php
}













// ========== 41. كروت مربعة + نيون متحرك ==========
add_action('wp_footer', 'pdp_neon_premium_ui', 994);
function pdp_neon_premium_ui() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;
    ?>
    <style>
    .pdp-card-image { padding-bottom: 100% !important; }
    @media (max-width: 768px) { .pdp-card-image { padding-bottom: 90% !important; } }
    @media (max-width: 400px) { .pdp-card-image { padding-bottom: 85% !important; } }
    </style>

    <script>
    (function(){
        var colors = [
            'rgba(212,168,83,.25)',
            'rgba(59,130,246,.25)',
            'rgba(139,92,246,.25)',
            'rgba(212,168,83,.25)'
        ];
        var step = 0;

        function tick() {
            step = (step + 1) % 360;
            var i = Math.floor((step / 360) * colors.length);
            var c = colors[i % colors.length];
            var c2 = colors[(i + 1) % colors.length];

            document.querySelectorAll('.pdp-project-card').forEach(function(card) {
                card.style.boxShadow = '0 0 15px ' + c + ', 0 0 30px ' + c2;
            });

            document.querySelectorAll('.pdp-project-card.pdp-featured').forEach(function(card) {
                card.style.boxShadow = '0 0 20px ' + c + ', 0 0 40px ' + c2;
            });

            requestAnimationFrame(tick);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ setTimeout(tick, 500); });
        } else {
            setTimeout(tick, 500);
        }
    })();
    </script>
    <?php
}














// ========== 42. عدد المشاريع جنب كل قطاع ومرحلة في الفلاتر ==========
add_action('wp_footer', 'pdp_filter_counts', 993);
function pdp_filter_counts() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;

    $is_english = has_shortcode($post->post_content, 'projects_directory_en');
    $lang = $is_english ? 'en' : 'ar';

    // جلب عدد المشاريع لكل قطاع بالـ term_id
    $sectors = get_terms(['taxonomy' => 'sector', 'hide_empty' => true]);
    $sector_counts = [];
    if (!is_wp_error($sectors)) {
        foreach ($sectors as $s) {
            $sector_counts[$s->term_id] = $s->count;
        }
    }

    // جلب عدد المشاريع لكل مرحلة بالـ term_id
    $stages = get_terms(['taxonomy' => 'project_stage', 'hide_empty' => true]);
    $stage_counts = [];
    if (!is_wp_error($stages)) {
        foreach ($stages as $s) {
            $stage_counts[$s->term_id] = $s->count;
        }
    }
    ?>
    <script>
    (function(){
        var lang = '<?php echo $lang; ?>';
        var sectorCounts = <?php echo json_encode($sector_counts); ?>;
        var stageCounts = <?php echo json_encode($stage_counts); ?>;

        function addCounts() {
            // القطاعات
            var sectorSelect = document.getElementById('pdpSectorFilter' + lang);
            if (sectorSelect) {
                Array.from(sectorSelect.options).forEach(function(opt) {
                    if (opt.value && sectorCounts[opt.value] && opt.textContent.indexOf('(') === -1) {
                        opt.textContent = opt.textContent + ' (' + sectorCounts[opt.value] + ')';
                    }
                });
            }

            // المراحل
            var stageSelect = document.getElementById('pdpStageFilter' + lang);
            if (stageSelect) {
                Array.from(stageSelect.options).forEach(function(opt) {
                    if (opt.value && stageCounts[opt.value] && opt.textContent.indexOf('(') === -1) {
                        opt.textContent = opt.textContent + ' (' + stageCounts[opt.value] + ')';
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(addCounts, 300); });
        } else {
            setTimeout(addCounts, 300);
        }
    })();
    </script>
    <?php
}









// ========== 43. تاجات قابلة للضغط - Clickable Tags ==========
add_action('wp_footer', 'pdp_clickable_tags_script', 992);
function pdp_clickable_tags_script() {
    global $post;
    if (!$post) return;
    $has_ar = has_shortcode($post->post_content, 'projects_directory');
    $has_en = has_shortcode($post->post_content, 'projects_directory_en');
    if (!$has_ar && !$has_en) return;

    $is_english = $has_en;
    $lang = $is_english ? 'en' : 'ar';

    // نبني map للقطاعات: اسم => term_id
    $sectors = get_terms(['taxonomy' => 'sector', 'hide_empty' => false]);
    $sector_map = [];
    if (!is_wp_error($sectors)) {
        foreach ($sectors as $s) {
            $sector_map[$s->name] = $s->term_id;
            // كمان نضيف الاسم الإنجليزي لو موجود
            $en_name = pdp_translate_term($s->name, 'sector', 'en');
            if ($en_name !== $s->name) {
                $sector_map[$en_name] = $s->term_id;
            }
        }
    }

    // نبني map للمراحل: اسم => term_id
    $stages = get_terms(['taxonomy' => 'project_stage', 'hide_empty' => false]);
    $stage_map = [];
    if (!is_wp_error($stages)) {
        foreach ($stages as $s) {
            $stage_map[$s->name] = $s->term_id;
            $en_name = pdp_translate_term($s->name, 'stage', 'en');
            if ($en_name !== $s->name) {
                $stage_map[$en_name] = $s->term_id;
            }
        }
    }
    ?>
    <style>
    .pdp-tag-sector,
    .pdp-tag-stage {
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        position: relative !important;
    }
    .pdp-tag-sector:hover {
        background: var(--pdp-primary, #1D2D51) !important;
        color: #fff !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(29, 45, 81, 0.3) !important;
    }
    .pdp-tag-sector:hover::before {
        background: #fff !important;
    }
    .pdp-tag-stage:hover {
        background: var(--pdp-gold, #d4a853) !important;
        color: #fff !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(212, 168, 83, 0.3) !important;
    }
    .pdp-tag-stage:hover::before {
        background: #fff !important;
    }
    /* نفس الشيء لصفحة المشروع المفردة */
    .pds-tag-sector,
    .pds-tag-stage {
        cursor: pointer !important;
        transition: all 0.3s ease !important;
    }
    .pds-tag-sector:hover {
        background: var(--pds-primary, #1D2D51) !important;
        color: #fff !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(29, 45, 81, 0.3) !important;
    }
    .pds-tag-sector:hover::before {
        background: #fff !important;
    }
    .pds-tag-stage:hover {
        background: var(--pds-gold, #d4a853) !important;
        color: #fff !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(212, 168, 83, 0.3) !important;
    }
    .pds-tag-stage:hover::before {
        background: #fff !important;
    }
    </style>

    <script>
    (function(){
        'use strict';

        var lang = '<?php echo $lang; ?>';
        var isEnglish = <?php echo $is_english ? 'true' : 'false'; ?>;
        var pageUrl = isEnglish ? '<?php echo esc_js(home_url('/pb-eng/')); ?>' : '<?php echo esc_js(home_url('/pb/')); ?>';

        var sectorMap = <?php echo json_encode($sector_map, JSON_UNESCAPED_UNICODE); ?>;
        var stageMap = <?php echo json_encode($stage_map, JSON_UNESCAPED_UNICODE); ?>;

        function makeTagsClickable() {
            // القطاعات
            document.querySelectorAll('.pdp-tag-sector, .pds-tag-sector').forEach(function(tag) {
                if (tag.dataset.clickable === 'true') return;
                tag.dataset.clickable = 'true';

                tag.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var text = this.textContent.trim();
                    var termId = sectorMap[text];
                    if (termId) {
                        window.location.href = pageUrl + '?sector=' + termId;
                    }
                });
            });

            // المراحل
            document.querySelectorAll('.pdp-tag-stage, .pds-tag-stage').forEach(function(tag) {
                if (tag.dataset.clickable === 'true') return;
                tag.dataset.clickable = 'true';

                tag.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var text = this.textContent.trim();
                    var termId = stageMap[text];
                    if (termId) {
                        window.location.href = pageUrl + '?stage=' + termId;
                    }
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(makeTagsClickable, 300); });
        } else {
            setTimeout(makeTagsClickable, 300);
        }

        // لو فيه AJAX أو تحديثات ديناميكية
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxComplete(function() { setTimeout(makeTagsClickable, 400); });
        }

        // Observer عشان لو كروت جديدة اتضافت
        var observer = new MutationObserver(function() { setTimeout(makeTagsClickable, 200); });
        var grid = document.getElementById('pdpProjectsGrid' + lang);
        if (grid) observer.observe(grid, { childList: true, subtree: true });
    })();
    </script>
    <?php
}








// ========== 44. مشاريع مشابهة - Related Projects Slider ==========
add_filter('the_content', 'pdp_append_related_projects', 30);
function pdp_append_related_projects($content) {
    if (!is_singular('project')) return $content;

    global $post;
    $pid = $post->ID;
    $lang = isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'ar';
    $is_english = ($lang === 'en');
    $page_url = $is_english ? home_url('/pb-eng/') : home_url('/pb/');

    $sectors = get_the_terms($pid, 'sector');
    if (!$sectors || is_wp_error($sectors)) return $content;
    $sector = $sectors[0];

    $related = new WP_Query([
        'post_type'      => 'project',
        'posts_per_page' => 12,
        'post__not_in'   => [$pid],
        'post_status'    => 'publish',
        'orderby'        => 'rand',
        'tax_query'      => [[
            'taxonomy' => 'sector',
            'field'    => 'term_id',
            'terms'    => $sector->term_id,
        ]],
    ]);

    if (!$related->have_posts()) {
        wp_reset_postdata();
        return $content;
    }

    $texts = $is_english
        ? ['title' => 'Similar Projects', 'subtitle' => 'More projects in', 'view' => 'View Details', 'view_all' => 'View All', 'prev' => '&#10094;', 'next' => '&#10095;']
        : ['title' => 'مشاريع مشابهة', 'subtitle' => 'مشاريع أخرى في قطاع', 'view' => 'عرض التفاصيل', 'view_all' => 'عرض الكل', 'prev' => '&#10095;', 'next' => '&#10094;'];

    $sector_name = pdp_translate_term($sector->name, 'sector', $lang);
    $sector_url = $page_url . '?sector=' . $sector->term_id;
    $dir = $is_english ? 'ltr' : 'rtl';

    $projects = [];
    while ($related->have_posts()) {
        $related->the_post();
        $rpid = get_the_ID();
        $pdata = pdp_get_project_data($rpid, $lang);
        $img = '';
        if (has_post_thumbnail($rpid)) {
            $img = get_the_post_thumbnail_url($rpid, 'medium');
        } elseif ($pdata['project_gallery']) {
            $gids = array_filter(explode(',', $pdata['project_gallery']));
            if (!empty($gids[0])) $img = wp_get_attachment_image_url(intval($gids[0]), 'medium');
        }
        if (!$img) $img = 'https://via.placeholder.com/400x400/1D2D51/C9A961?text=' . urlencode($pdata['title']);

        $link = get_permalink($rpid) . ($is_english ? '?lang=en' : '');
        $excerpt = !empty($pdata['excerpt']) ? $pdata['excerpt'] : $pdata['project_details'];
        $excerpt = $excerpt ? wp_trim_words(strip_tags($excerpt), 10, '...') : '';
        $is_feat = ($pdata['is_featured'] === '1');

        $stage_display = '';
        $pstages = get_the_terms($rpid, 'project_stage');
        if ($pstages && !is_wp_error($pstages)) {
            $stage_display = pdp_translate_term($pstages[0]->name, 'stage', $lang);
        }

        $projects[] = [
            'title'       => $pdata['title'],
            'img'         => $img,
            'link'        => $link,
            'excerpt'     => $excerpt,
            'stage'       => $stage_display,
            'is_featured' => $is_feat,
        ];
    }
    wp_reset_postdata();

    if (empty($projects)) return $content;

    ob_start();
    ?>
    <style>
    .pdp-related-section{max-width:900px;margin:40px auto 20px;padding:0 15px;direction:<?php echo $dir;?>;font-family:<?php echo $is_english?"'Inter','Segoe UI',sans-serif":"'Cairo','Segoe UI',sans-serif";?>}
    .pdp-related-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;gap:15px;flex-wrap:wrap}
    .pdp-related-title-wrap{display:flex;flex-direction:column;gap:4px}
    .pdp-related-title{font-size:1.35rem;font-weight:900;color:#1D2D51;display:flex;align-items:center;gap:10px;margin:0;line-height:1.3}
    .pdp-related-title::before{content:'';width:5px;height:28px;background:linear-gradient(180deg,#d4a853,#f0d78c);border-radius:3px;flex-shrink:0}
    .pdp-related-subtitle{font-size:.88rem;color:#64748b;font-weight:600;padding-right:15px;padding-left:15px}
    .pdp-related-subtitle a{color:#1D2D51;font-weight:800;text-decoration:none;border-bottom:2px solid #d4a853;transition:all .3s}
    .pdp-related-subtitle a:hover{color:#b8923d}
    .pdp-related-controls{display:flex;align-items:center;gap:8px}
    .pdp-related-nav-btn{width:42px;height:42px;border-radius:50%;border:2px solid #e2e8f0;background:#fff;color:#1D2D51;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:bold;transition:all .3s;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:0}
    .pdp-related-nav-btn:hover{background:#d4a853;color:#fff!important;border-color:#d4a853;transform:scale(1.1);box-shadow:0 4px 15px rgba(212,168,83,.35)}
    .pdp-related-nav-btn:disabled{opacity:.3;cursor:not-allowed;transform:none}
    .pdp-related-view-all{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#1D2D51;color:#fff!important;border-radius:25px;text-decoration:none;font-size:.8rem;font-weight:700;transition:all .3s}
    .pdp-related-view-all:hover{background:#d4a853;color:#1D2D51!important;transform:translateY(-2px);box-shadow:0 4px 15px rgba(212,168,83,.3)}
    .pdp-related-view-all svg{width:14px;height:14px}
    .pdp-related-track-wrapper{overflow:hidden;border-radius:16px;position:relative}
    .pdp-related-track{display:flex;gap:18px;transition:transform .5s cubic-bezier(.4,0,.2,1);will-change:transform}
    .pdp-related-card{min-width:calc(33.333% - 12px);max-width:calc(33.333% - 12px);background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);border:1px solid #e2e8f0;transition:all .4s;flex-shrink:0;display:flex;flex-direction:column;position:relative}
    .pdp-related-card:hover{transform:translateY(-6px);box-shadow:0 15px 40px rgba(0,0,0,.12);border-color:rgba(212,168,83,.3)}
    .pdp-related-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#d4a853,#f0d78c,#d4a853);transform:scaleX(0);transition:transform .4s;z-index:5}
    .pdp-related-card:hover::before{transform:scaleX(1)}
    .pdp-related-card.is-featured{border:2px solid #d4a853;background:linear-gradient(145deg,#fffef7,#fefcf0)}
    .pdp-related-card.is-featured::before{transform:scaleX(1);background:linear-gradient(90deg,#b8923d,#d4a853,#f0d78c)}
    .pdp-related-featured-badge{position:absolute;top:10px;left:10px;z-index:10;width:32px;height:32px;background:linear-gradient(135deg,#e8f4fd,#d0e8fa);border:2px solid #d4a853;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(212,168,83,.35)}
    .pdp-related-featured-badge img{width:20px;height:20px;border-radius:50%;object-fit:contain}
    .pdp-related-img{position:relative;width:100%;padding-bottom:85%;overflow:hidden;background:linear-gradient(135deg,#f1f5f9,#e2e8f0)}
    .pdp-related-img img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:transform .5s}
    .pdp-related-card:hover .pdp-related-img img{transform:scale(1.06)}
    .pdp-related-body{padding:14px;flex:1;display:flex;flex-direction:column}
    .pdp-related-card-title{font-size:.95rem;font-weight:800;color:#1D2D51;margin-bottom:6px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;transition:color .3s}
    .pdp-related-card:hover .pdp-related-card-title{color:#b8923d}
    .pdp-related-card-excerpt{font-size:.8rem;color:#64748b;line-height:1.5;margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .pdp-related-card-stage{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:rgba(212,168,83,.1);color:#b8923d;border-radius:5px;font-size:.7rem;font-weight:700;margin-bottom:12px;width:fit-content}
    .pdp-related-card-stage::before{content:'';width:5px;height:5px;border-radius:50%;background:#d4a853}
    .pdp-related-card-btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;background:#1D2D51;color:#fff!important;border-radius:8px;text-decoration:none;font-size:.82rem;font-weight:700;transition:all .3s;margin-top:auto}
    .pdp-related-card-btn:hover{background:#d4a853;color:#1D2D51!important;transform:translateY(-2px);box-shadow:0 4px 15px rgba(212,168,83,.3)}
    .pdp-related-card-btn svg{width:16px;height:16px}
    .pdp-related-dots{display:flex;justify-content:center;gap:8px;margin-top:20px}
    .pdp-related-dot{width:10px;height:10px;border-radius:50%;background:#e2e8f0;border:none;cursor:pointer;padding:0;transition:all .3s}
    .pdp-related-dot.active{background:#d4a853;transform:scale(1.3);box-shadow:0 2px 8px rgba(212,168,83,.4)}
    .pdp-related-dot:hover:not(.active){background:#94a3b8;transform:scale(1.1)}
    @media(max-width:1024px){.pdp-related-card{min-width:calc(50% - 9px);max-width:calc(50% - 9px)}}
    @media(max-width:768px){.pdp-related-section{margin-top:30px;padding:0 12px}.pdp-related-header{margin-bottom:16px}.pdp-related-title{font-size:1.15rem}.pdp-related-subtitle{font-size:.82rem}.pdp-related-nav-btn{width:36px;height:36px;font-size:14px}.pdp-related-card{min-width:calc(80% - 9px);max-width:calc(80% - 9px)}.pdp-related-track{gap:14px}.pdp-related-view-all{padding:7px 14px;font-size:.75rem}}
    @media(max-width:480px){.pdp-related-card{min-width:calc(85% - 7px);max-width:calc(85% - 7px)}.pdp-related-body{padding:12px}.pdp-related-card-title{font-size:.9rem}.pdp-related-img{padding-bottom:80%}}
    </style>

    <div class="pdp-related-section" id="pdpRelatedSection">
        <div class="pdp-related-header">
            <div class="pdp-related-title-wrap">
                <h2 class="pdp-related-title"><?php echo $texts['title']; ?></h2>
                <span class="pdp-related-subtitle"><?php echo $texts['subtitle']; ?> <a href="<?php echo esc_url($sector_url); ?>"><?php echo esc_html($sector_name); ?></a></span>
            </div>
            <div class="pdp-related-controls">
                <button type="button" class="pdp-related-nav-btn" id="pdpRelPrev"><?php echo $texts['prev']; ?></button>
                <button type="button" class="pdp-related-nav-btn" id="pdpRelNext"><?php echo $texts['next']; ?></button>
                <a href="<?php echo esc_url($sector_url); ?>" class="pdp-related-view-all"><?php echo $texts['view_all']; ?><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M<?php echo $is_english?'13 7l5 5m0 0l-5 5m5-5H6':'11 17l-5-5m0 0l5-5m-5 5h12';?>"/></svg></a>
            </div>
        </div>
        <div class="pdp-related-track-wrapper">
            <div class="pdp-related-track" id="pdpRelTrack">
                <?php foreach ($projects as $p): ?>
                <div class="pdp-related-card<?php echo $p['is_featured']?' is-featured':''; ?>">
                    <?php if ($p['is_featured']): ?>
                    <div class="pdp-related-featured-badge"><img src="http://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp" alt="Featured"></div>
                    <?php endif; ?>
                    <div class="pdp-related-img"><img src="<?php echo esc_url($p['img']); ?>" alt="<?php echo esc_attr($p['title']); ?>" loading="lazy"></div>
                    <div class="pdp-related-body">
                        <h3 class="pdp-related-card-title"><?php echo esc_html($p['title']); ?></h3>
                        <?php if ($p['excerpt']): ?><p class="pdp-related-card-excerpt"><?php echo esc_html($p['excerpt']); ?></p><?php endif; ?>
                        <?php if ($p['stage']): ?><span class="pdp-related-card-stage"><?php echo esc_html($p['stage']); ?></span><?php endif; ?>
                        <a href="<?php echo esc_url($p['link']); ?>" class="pdp-related-card-btn"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><?php echo $texts['view']; ?></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="pdp-related-dots" id="pdpRelDots"></div>
    </div>

    <script>
    (function(){
        'use strict';
        var track=document.getElementById('pdpRelTrack'),prevBtn=document.getElementById('pdpRelPrev'),nextBtn=document.getElementById('pdpRelNext'),dotsC=document.getElementById('pdpRelDots');
        if(!track||!prevBtn||!nextBtn)return;
        var cards=Array.from(track.querySelectorAll('.pdp-related-card')),total=cards.length;
        if(total===0)return;
        var idx=0,timer,isRTL=<?php echo $is_english?'false':'true';?>;
        function visCount(){var w=window.innerWidth;if(w<=480)return 1;if(w<=768)return 1;if(w<=1024)return 2;return 3}
        function maxIdx(){return Math.max(0,total-visCount())}
        function buildDots(){dotsC.innerHTML='';for(var i=0;i<=maxIdx();i++){var d=document.createElement('button');d.type='button';d.className='pdp-related-dot'+(i===0?' active':'');d.dataset.i=i;d.onclick=function(){goTo(parseInt(this.dataset.i));resetAP()};dotsC.appendChild(d)}}
        function updDots(){dotsC.querySelectorAll('.pdp-related-dot').forEach(function(d,i){d.classList.toggle('active',i===idx)})}
        function updBtns(){prevBtn.disabled=(idx<=0);nextBtn.disabled=(idx>=maxIdx())}
        function goTo(i){var mx=maxIdx();if(i<0)i=0;if(i>mx)i=mx;idx=i;var cw=cards[0].offsetWidth,g=18,off=idx*(cw+g);track.style.transform=isRTL?'translateX('+off+'px)':'translateX(-'+off+'px)';updDots();updBtns()}
        function goNext(){idx>=maxIdx()?goTo(0):goTo(idx+1)}
        function goPrev(){idx<=0?goTo(maxIdx()):goTo(idx-1)}
        prevBtn.onclick=function(){goPrev();resetAP()};
        nextBtn.onclick=function(){goNext();resetAP()};
        var sx=0;
        track.addEventListener('touchstart',function(e){sx=e.changedTouches[0].screenX},{passive:true});
        track.addEventListener('touchend',function(e){var diff=sx-e.changedTouches[0].screenX;if(isRTL)diff=-diff;if(Math.abs(diff)>50){diff>0?goNext():goPrev();resetAP()}},{passive:true});
        function startAP(){timer=setInterval(goNext,4000)}
        function resetAP(){clearInterval(timer);startAP()}
        var sec=document.getElementById('pdpRelatedSection');
        if(sec){sec.onmouseenter=function(){clearInterval(timer)};sec.onmouseleave=function(){startAP()}}
        var rt;window.addEventListener('resize',function(){clearTimeout(rt);rt=setTimeout(function(){buildDots();goTo(Math.min(idx,maxIdx()))},200)});
        buildDots();updBtns();startAP();
    })();
    </script>
    <?php
    $related_html = ob_get_clean();
    return $content . $related_html;
}














// ========== 45. إصلاح شامل لفلتر القطاعات + بحث محسّن + عداد الفئات ==========

/**
 * المشكلة: قطاع "الخدمات المالية والتكنولوجيا المالية (FinTech)" 
 * الأقواس بتسبب مشاكل في الـ URL encoding والبحث
 * 
 * الحل:
 * 1. إصلاح الفلتر عشان يستخدم term_id دايماً
 * 2. تحسين البحث عشان يدور في القطاع + اسم المشروع
 * 3. إضافة عداد مشاريع لكل فئة في صفحة النتائج
 */

// === الجزء 1: إصلاح فلتر القطاعات في JavaScript ===
add_action('wp_footer', 'pdp_fix_fintech_filter', 990);
function pdp_fix_fintech_filter() {
    global $post;
    if (!$post) return;
    $has_ar = has_shortcode($post->post_content, 'projects_directory');
    $has_en = has_shortcode($post->post_content, 'projects_directory_en');
    if (!$has_ar && !$has_en) return;

    $is_english = $has_en;
    $lang = $is_english ? 'en' : 'ar';

    // بناء بيانات القطاعات مع العدد
    $sectors = pdp_get_ordered_sectors(true);
    $sector_data = [];
    foreach ($sectors as $s) {
        $sector_data[] = [
            'id'    => $s->term_id,
            'name'  => $is_english ? pdp_translate_term($s->name, 'sector', 'en') : $s->name,
            'slug'  => $s->slug,
            'count' => $s->count,
        ];
    }

    // بناء بيانات المراحل مع العدد
    $stages_list = get_terms(['taxonomy' => 'project_stage', 'hide_empty' => true]);
    $stage_data = [];
    if (!is_wp_error($stages_list)) {
        foreach ($stages_list as $s) {
            $stage_data[] = [
                'id'    => $s->term_id,
                'name'  => $is_english ? pdp_translate_term($s->name, 'stage', 'en') : $s->name,
                'slug'  => $s->slug,
                'count' => $s->count,
            ];
        }
    }

    // بناء map: كل مشروع وقطاعاته (عشان البحث المحلي)
    $project_sectors_map = [];
    $all_projects = get_posts([
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ]);
    foreach ($all_projects as $proj_id) {
        $proj_sectors = get_the_terms($proj_id, 'sector');
        $proj_stages  = get_the_terms($proj_id, 'project_stage');
        $s_names = [];
        $st_names = [];
        if ($proj_sectors && !is_wp_error($proj_sectors)) {
            foreach ($proj_sectors as $ps) {
                $s_names[] = $ps->name;
                $en = pdp_translate_term($ps->name, 'sector', 'en');
                if ($en !== $ps->name) $s_names[] = $en;
            }
        }
        if ($proj_stages && !is_wp_error($proj_stages)) {
            foreach ($proj_stages as $pst) {
                $st_names[] = $pst->name;
                $en = pdp_translate_term($pst->name, 'stage', 'en');
                if ($en !== $pst->name) $st_names[] = $en;
            }
        }
        $project_sectors_map[$proj_id] = [
            'sectors' => $s_names,
            'stages'  => $st_names,
        ];
    }
    ?>
    <style>
    /* === عداد الفئة المختارة === */
    .pdp-active-filter-banner {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 14px;
        padding: 16px 24px;
        margin-bottom: 18px;
        background: linear-gradient(135deg, rgba(212,168,83,.08) 0%, rgba(29,45,81,.05) 100%);
        border: 2px solid rgba(212,168,83,.25);
        border-radius: 14px;
        flex-wrap: wrap;
        animation: pdpBannerIn 0.4s ease;
    }
    .pdp-active-filter-banner.show { display: flex; }
    @keyframes pdpBannerIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .pdp-active-filter-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--pdp-gold, #d4a853), var(--pdp-gold-light, #f0d78c));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(212,168,83,.3);
    }
    .pdp-active-filter-icon svg {
        width: 24px;
        height: 24px;
        color: var(--pdp-primary-dark, #0f1829);
    }
    .pdp-active-filter-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        min-width: 200px;
    }
    .pdp-active-filter-name {
        font-size: 1.1rem;
        font-weight: 900;
        color: var(--pdp-primary, #1D2D51);
        line-height: 1.3;
    }
    .pdp-active-filter-count-text {
        font-size: 0.88rem;
        color: var(--pdp-text-light, #64748b);
        font-weight: 600;
    }
    .pdp-active-filter-count-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--pdp-primary, #1D2D51);
        color: #fff;
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border-radius: 20px;
        font-size: 1rem;
        font-weight: 900;
        box-shadow: 0 3px 10px rgba(29,45,81,.25);
    }
    .pdp-active-filter-clear {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        background: rgba(239,68,68,.08);
        color: #ef4444 !important;
        border: 2px solid rgba(239,68,68,.2);
        border-radius: 25px;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    .pdp-active-filter-clear:hover {
        background: #ef4444;
        color: #fff !important;
        border-color: #ef4444;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(239,68,68,.3);
    }
    .pdp-active-filter-clear svg {
        width: 16px;
        height: 16px;
    }

    @media (max-width: 768px) {
        .pdp-active-filter-banner {
            padding: 14px 16px;
            gap: 10px;
            border-radius: 12px;
        }
        .pdp-active-filter-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }
        .pdp-active-filter-icon svg { width: 20px; height: 20px; }
        .pdp-active-filter-name { font-size: 1rem; }
        .pdp-active-filter-count-text { font-size: 0.82rem; }
        .pdp-active-filter-count-num { min-width: 28px; height: 28px; font-size: 0.9rem; }
        .pdp-active-filter-clear { padding: 7px 14px; font-size: 0.78rem; }
    }
    </style>

    <script>
    (function(){
        'use strict';

        var lang = '<?php echo $lang; ?>';
        var isEnglish = <?php echo $is_english ? 'true' : 'false'; ?>;
        var sectorData = <?php echo json_encode($sector_data, JSON_UNESCAPED_UNICODE); ?>;
        var stageData = <?php echo json_encode($stage_data, JSON_UNESCAPED_UNICODE); ?>;
        var projectMeta = <?php echo json_encode($project_sectors_map, JSON_UNESCAPED_UNICODE); ?>;

        var texts = isEnglish
            ? {
                projectsIn: 'projects in',
                projectIn: 'project in',
                clearFilter: 'Clear',
                allSectors: 'All Sectors',
                allStages: 'All Stages'
              }
            : {
                projectsIn: 'مشروع في قطاع',
                projectIn: 'مشروع في قطاع',
                clearFilter: 'مسح',
                allSectors: 'جميع القطاعات',
                allStages: 'جميع المراحل'
              };

        var pageBaseUrl = isEnglish
            ? '<?php echo esc_js(home_url('/pb-eng/')); ?>'
            : '<?php echo esc_js(home_url('/pb/')); ?>';

        function init() {
            fixFilterBehavior();
            showActiveBanner();
            // enhanceSearchWithSectors(); // مُعطّلة — البحث موحّد في القسم 40
        }

        // === إصلاح سلوك الفلاتر - يتأكد إن term_id هو اللي بيتبعت ===
        function fixFilterBehavior() {
            var sectorSel = document.getElementById('pdpSectorFilter' + lang);
            var stageSel = document.getElementById('pdpStageFilter' + lang);
            var orderSel = document.getElementById('pdpOrderFilter' + lang);

            if (!sectorSel) return;

            // نشيل الـ event listeners القديمة ونحط جديدة
            var newSectorSel = sectorSel.cloneNode(true);
            sectorSel.parentNode.replaceChild(newSectorSel, sectorSel);

            var newStageSel = stageSel ? stageSel.cloneNode(true) : null;
            if (stageSel && newStageSel) {
                stageSel.parentNode.replaceChild(newStageSel, stageSel);
            }

            var newOrderSel = orderSel ? orderSel.cloneNode(true) : null;
            if (orderSel && newOrderSel) {
                orderSel.parentNode.replaceChild(newOrderSel, orderSel);
            }

            // نعمل event listener جديد يبعت term_id صح
            function handleFilterChange() {
                var url = new URL(pageBaseUrl, window.location.origin);

                var sv = document.getElementById('pdpSectorFilter' + lang);
                var stv = document.getElementById('pdpStageFilter' + lang);
                var ov = document.getElementById('pdpOrderFilter' + lang);

                if (sv && sv.value) {
                    url.searchParams.set('sector', sv.value);
                }

                if (stv && stv.value) {
                    url.searchParams.set('stage', stv.value);
                }

                if (ov && ov.value && ov.value !== 'date') {
                    if (ov.value === 'discover') {
                        // الـ Discover بيشتغل client-side
                        return;
                    }
                    url.searchParams.set('orderby', ov.value);
                }

                window.location.href = url.toString();
            }

            var refreshedSector = document.getElementById('pdpSectorFilter' + lang);
            var refreshedStage = document.getElementById('pdpStageFilter' + lang);
            var refreshedOrder = document.getElementById('pdpOrderFilter' + lang);

            if (refreshedSector) {
                refreshedSector.addEventListener('change', handleFilterChange);
            }
            if (refreshedStage) {
                refreshedStage.addEventListener('change', handleFilterChange);
            }
            if (refreshedOrder) {
                refreshedOrder.addEventListener('change', function() {
                    if (this.value === 'discover') return; // يخلي القسم 37 يتعامل
                    handleFilterChange();
                });
            }
        }

        // === عرض بانر الفئة المختارة مع العداد ===
        function showActiveBanner() {
            var url = new URL(window.location.href);
            var sectorId = url.searchParams.get('sector');
            var stageId = url.searchParams.get('stage');

            if (!sectorId && !stageId) return;

            var filterName = '';
            var filterCount = 0;
            var filterType = '';
            var iconSvg = '';

            if (sectorId) {
                var found = sectorData.find(function(s) {
                    return String(s.id) === String(sectorId) || s.slug === sectorId;
                });
                if (found) {
                    filterName = found.name;
                    filterCount = found.count;
                    filterType = isEnglish ? 'Sector' : 'القطاع';
                    iconSvg = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>';
                }
            }

            if (stageId) {
                var foundStage = stageData.find(function(s) {
                    return String(s.id) === String(stageId) || s.slug === stageId;
                });
                if (foundStage) {
                    var stageName = foundStage.name;
                    var stageCount = foundStage.count;
                    if (filterName) {
                        filterName += ' + ' + stageName;
                    } else {
                        filterName = stageName;
                        filterCount = stageCount;
                        filterType = isEnglish ? 'Stage' : 'المرحلة';
                    }
                    iconSvg = iconSvg || '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
                }
            }

            if (!filterName) return;

            var grid = document.getElementById('pdpProjectsGrid' + lang);
            if (!grid) return;

            // نحسب العدد الفعلي للمشاريع الظاهرة
            var visibleCards = grid.querySelectorAll('.pdp-project-card');
            var actualCount = visibleCards.length;

            var countText = isEnglish
                ? actualCount + ' ' + (actualCount === 1 ? 'project' : 'projects')
                : actualCount + ' ' + texts.projectsIn;

            var banner = document.createElement('div');
            banner.className = 'pdp-active-filter-banner show';
            banner.id = 'pdpActiveFilterBanner';
            banner.innerHTML = ''
                + '<div class="pdp-active-filter-icon">' + iconSvg + '</div>'
                + '<div class="pdp-active-filter-info">'
                + '  <div class="pdp-active-filter-name">' + filterName + '</div>'
                + '  <div class="pdp-active-filter-count-text">' + countText + '</div>'
                + '</div>'
                + '<span class="pdp-active-filter-count-num">' + actualCount + '</span>'
                + '<a href="' + pageBaseUrl + '" class="pdp-active-filter-clear">'
                + '  <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>'
                + '  <span>' + texts.clearFilter + '</span>'
                + '</a>';

            // نحطه قبل الـ grid
            var filters = document.querySelector('.pdp-filters');
            if (filters && filters.nextSibling) {
                filters.parentNode.insertBefore(banner, filters.nextSibling);
            }
        }

        // === تحسين البحث المحلي - يدور في القطاع والمرحلة كمان ===
        function enhanceSearchWithSectors() {
            // ننتظر البحث الأصلي (القسم 40) يتحمل
            var attempts = 0;
            var waitForSearch = setInterval(function() {
                attempts++;
                var searchInput = document.getElementById('pdpSearchInput');
                if (searchInput || attempts > 30) {
                    clearInterval(waitForSearch);
                    if (searchInput) {
                        patchSearch(searchInput);
                    }
                }
            }, 200);
        }

        function patchSearch(input) {
            // نخزن الـ original handler ونعمل override
            var grid = document.getElementById('pdpProjectsGrid' + lang);
            if (!grid) return;

            // نضيف data attributes على كل كارت فيها بيانات القطاع والمرحلة
            var cards = grid.querySelectorAll('.pdp-project-card');
            cards.forEach(function(card) {
                // نجيب عنوان المشروع
                var titleEl = card.querySelector('.pdp-card-title');
                var title = titleEl ? titleEl.textContent.trim() : '';

                // نجيب القطاع والمرحلة من التاجات
                var sectorTag = card.querySelector('.pdp-tag-sector');
                var stageTag = card.querySelector('.pdp-tag-stage');
                var sectorText = sectorTag ? sectorTag.textContent.trim() : '';
                var stageText = stageTag ? stageTag.textContent.trim() : '';

                // نجيب الـ excerpt
                var excerptEl = card.querySelector('.pdp-card-excerpt');
                var excerptText = excerptEl ? excerptEl.textContent.trim() : '';

                // نجيب اسم المؤسس من الـ contact button
                var contactBtn = card.querySelector('.pdp-btn-contact');
                var founderName = contactBtn ? (contactBtn.getAttribute('data-founder') || '') : '';

                // نخزن كل البيانات القابلة للبحث
                var searchData = [title, sectorText, stageText, excerptText, founderName].join(' ').toLowerCase();
                card.setAttribute('data-search-text', searchData);
            });

            // نعمل override للبحث
            var countEl = document.getElementById('pdpSearchCount');
            var noResultsEl = document.getElementById('pdpSearchNoResults');
            var clearBtn = document.getElementById('pdpSearchClear');

            var textsSearch = isEnglish
                ? { resultCount: 'projects', resultOne: 'project' }
                : { resultCount: 'مشروع', resultOne: 'مشروع' };

            var debounceTimer;
            
            // نشيل الـ listeners القديمة ونحط جديدة
            var newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            
            // نربط الـ clear button تاني
            var newClearBtn = document.getElementById('pdpSearchClear');

            newInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                var self = this;
                debounceTimer = setTimeout(function() {
                    doEnhancedSearch(self.value.trim(), grid, countEl, noResultsEl, textsSearch);
                }, 200);
                if (this.value.trim().length > 0) {
                    if (newClearBtn) newClearBtn.classList.add('show');
                } else {
                    if (newClearBtn) newClearBtn.classList.remove('show');
                }
            });

            if (newClearBtn) {
                newClearBtn.addEventListener('click', function() {
                    newInput.value = '';
                    this.classList.remove('show');
                    doEnhancedSearch('', grid, countEl, noResultsEl, textsSearch);
                    newInput.focus();
                });
            }

            newInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    if (newClearBtn) newClearBtn.classList.remove('show');
                    doEnhancedSearch('', grid, countEl, noResultsEl, textsSearch);
                    this.blur();
                }
            });
        }

        function doEnhancedSearch(query, grid, countEl, noResultsEl, textsSearch) {
            var cards = Array.from(grid.querySelectorAll('.pdp-project-card'));
            var visibleCount = 0;

            if (!query) {
                cards.forEach(function(card) {
                    card.classList.remove('pdp-search-hidden', 'pdp-search-visible');
                    removeHighlights(card);
                });
                if (countEl) countEl.classList.remove('show');
                if (noResultsEl) noResultsEl.classList.remove('show');
                grid.style.display = '';
                return;
            }

            var queryLower = query.toLowerCase();
            var queryWords = queryLower.split(/\s+/).filter(function(w) { return w.length > 0; });

            cards.forEach(function(card) {
                // نستخدم البيانات المخزنة اللي فيها القطاع والمرحلة
                var searchText = card.getAttribute('data-search-text') || '';
                
                // لو مفيش data attribute، نبني من الكارت
                if (!searchText) {
                    var titleEl = card.querySelector('.pdp-card-title');
                    var sectorTag = card.querySelector('.pdp-tag-sector');
                    var stageTag = card.querySelector('.pdp-tag-stage');
                    var excerptEl = card.querySelector('.pdp-card-excerpt');
                    searchText = [
                        titleEl ? titleEl.textContent : '',
                        sectorTag ? sectorTag.textContent : '',
                        stageTag ? stageTag.textContent : '',
                        excerptEl ? excerptEl.textContent : ''
                    ].join(' ').toLowerCase();
                }

                var matches = queryWords.every(function(word) {
                    return searchText.indexOf(word) !== -1;
                });

                if (matches) {
                    card.classList.remove('pdp-search-hidden');
                    card.classList.add('pdp-search-visible');
                    highlightInCard(card, queryWords);
                    visibleCount++;
                } else {
                    card.classList.add('pdp-search-hidden');
                    card.classList.remove('pdp-search-visible');
                    removeHighlights(card);
                }
            });

            if (visibleCount > 0) {
                var countText = visibleCount + ' ' + (visibleCount === 1 ? textsSearch.resultOne : textsSearch.resultCount);
                if (countEl) {
                    countEl.textContent = countText;
                    countEl.classList.add('show');
                }
                if (noResultsEl) noResultsEl.classList.remove('show');
                grid.style.display = '';
            } else {
                if (countEl) countEl.classList.remove('show');
                if (noResultsEl) noResultsEl.classList.add('show');
                grid.style.display = 'none';
            }
        }

        function highlightInCard(card, words) {
            removeHighlights(card);
            // نعمل highlight في العنوان والقطاع والمرحلة
            var elements = card.querySelectorAll('.pdp-card-title, .pdp-tag-sector, .pdp-tag-stage');
            elements.forEach(function(el) {
                var html = el.textContent;
                words.forEach(function(word) {
                    if (word.length < 1) return;
                    var escaped = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    var regex = new RegExp('(' + escaped + ')', 'gi');
                    html = html.replace(regex, '<span class="pdp-search-highlight">$1</span>');
                });
                el.innerHTML = html;
            });
        }

        function removeHighlights(card) {
            var highlights = card.querySelectorAll('.pdp-search-highlight');
            highlights.forEach(function(hl) {
                var parent = hl.parentNode;
                parent.replaceChild(document.createTextNode(hl.textContent), hl);
                parent.normalize();
            });
        }

        // === تشغيل ===
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(init, 350); });
        } else {
            setTimeout(init, 350);
        }
    })();
    </script>
    <?php
}









add_action('wp_footer', 'pdp_fix_search_across_pages', 9998);
function pdp_fix_search_across_pages() {
    if (!is_page(['pb', 'pb-eng'])) return;
    
    $all_projects = [];
    $pq = new WP_Query([
        'post_type' => 'project',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
    if ($pq->have_posts()) {
        while ($pq->have_posts()) {
            $pq->the_post();
            $pid = get_the_ID();
            $sectors = wp_get_post_terms($pid, 'project_sector', ['fields' => 'names']);
            $stages = wp_get_post_terms($pid, 'project_stage', ['fields' => 'names']);
            $all_projects[] = [
                'id' => $pid,
                'title' => get_the_title(),
                'sector' => (!is_wp_error($sectors) && !empty($sectors)) ? implode(' ', $sectors) : '',
                'stage' => (!is_wp_error($stages) && !empty($stages)) ? implode(' ', $stages) : '',
                'excerpt' => wp_strip_all_tags(get_the_excerpt()),
                'link' => get_permalink(),
                'img' => get_the_post_thumbnail_url($pid, 'medium') ?: '',
            ];
        }
        wp_reset_postdata();
    }
    $projects_json = json_encode($all_projects, JSON_UNESCAPED_UNICODE);
    ?>
    <script>
    (function(){
        var ALL_PROJECTS = <?php echo $projects_json; ?>;
        
        function fixSearch(){
            var input = document.getElementById('pdpSearchInput');
            if(!input) return;
            
            var newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            input = newInput;
            
            var clearBtn = document.getElementById('pdpSearchClear');
            var countEl = document.getElementById('pdpSearchCount');
            var noResultsEl = document.getElementById('pdpSearchNoResults');
            var lang = window.location.href.indexOf('pb-eng') !== -1 ? 'en' : 'ar';
            var grid = document.getElementById('pdpProjectsGrid' + lang);
            if(!grid) return;

            var debounceTimer;

            input.addEventListener('input', function(){
                var val = this.value.trim();
                if(clearBtn){
                    if(val.length > 0) clearBtn.classList.add('show');
                    else clearBtn.classList.remove('show');
                }
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function(){
                    doFullSearch(val, grid, countEl, noResultsEl);
                }, 300);
            });

            input.addEventListener('keydown', function(e){
                if(e.key === 'Escape'){
                    this.value = '';
                    if(clearBtn) clearBtn.classList.remove('show');
                    doFullSearch('', grid, countEl, noResultsEl);
                    this.blur();
                }
            });

            if(clearBtn){
                var nc = clearBtn.cloneNode(true);
                clearBtn.parentNode.replaceChild(nc, clearBtn);
                nc.addEventListener('click', function(){
                    input.value = '';
                    this.classList.remove('show');
                    doFullSearch('', grid, countEl, noResultsEl);
                    input.focus();
                });
            }
        }

        function doFullSearch(query, grid, countEl, noResultsEl){
            // أولاً: نرجّع كل الكروت الأصلية
            var cards = Array.from(grid.querySelectorAll('.pdp-project-card'));
            
            // نشيل أي نتائج AJAX سابقة
            var oldAjax = grid.querySelectorAll('.pdp-ajax-result-card');
            oldAjax.forEach(function(c){ c.remove(); });

            if(!query){
                cards.forEach(function(card){
                    card.classList.remove('pdp-search-hidden','pdp-search-visible');
                });
                if(countEl) countEl.classList.remove('show');
                if(noResultsEl) noResultsEl.classList.remove('show');
                grid.style.display = '';
                // أرجع الـ pagination
                var pag = document.querySelector('.pdp-pagination');
                if(pag) pag.style.display = '';
                return;
            }

            var qLower = query.toLowerCase();
            var words = qLower.split(/\s+/).filter(function(w){ return w.length > 0; });

            // ندور في كل المشاريع (من السيرفر)
            var matched = ALL_PROJECTS.filter(function(p){
                var text = (p.title + ' ' + p.sector + ' ' + p.stage + ' ' + p.excerpt).toLowerCase();
                return words.every(function(w){ return text.indexOf(w) !== -1; });
            });

            // نخبي كل الكروت الأصلية
            cards.forEach(function(card){
                card.classList.add('pdp-search-hidden');
                card.classList.remove('pdp-search-visible');
            });

            // نخبي الـ pagination
            var pag = document.querySelector('.pdp-pagination');
            if(pag) pag.style.display = 'none';

            if(matched.length > 0){
                // نظهر الكروت اللي موجودة في الصفحة الحالية
                matched.forEach(function(proj){
                    // ندور على الكارت في الـ DOM
                    var found = false;
                    cards.forEach(function(card){
                        var titleEl = card.querySelector('.pdp-card-title');
                        if(titleEl && titleEl.textContent.trim() === proj.title){
                            card.classList.remove('pdp-search-hidden');
                            card.classList.add('pdp-search-visible');
                            found = true;
                        }
                    });
                    
                    // لو مش موجود في الصفحة — نعمل كارت مؤقت
                    if(!found){
                        var tempCard = document.createElement('div');
                        tempCard.className = 'pdp-project-card pdp-ajax-result-card pdp-search-visible';
                        tempCard.style.opacity = '1';
                        tempCard.style.transform = 'translateY(0)';
                        tempCard.innerHTML = '<div class="pdp-card-image" style="padding-bottom:100%">'
                            + (proj.img ? '<img src="'+proj.img+'" alt="'+proj.title+'" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover">' : '')
                            + '</div>'
                            + '<div class="pdp-card-body">'
                            + '<h3 class="pdp-card-title">'+proj.title+'</h3>'
                            + '<p class="pdp-card-excerpt">'+proj.excerpt+'</p>'
                            + '<div class="pdp-card-tags">'
                            + (proj.sector ? '<span class="pdp-tag pdp-tag-sector">'+proj.sector+'</span>' : '')
                            + (proj.stage ? '<span class="pdp-tag pdp-tag-stage">'+proj.stage+'</span>' : '')
                            + '</div>'
                            + '<div class="pdp-card-actions">'
                            + '<a href="'+proj.link+'" class="pdp-action-btn pdp-btn-details">تفاصيل المشروع</a>'
                            + '</div></div>';
                        grid.appendChild(tempCard);
                    }
                });

                if(countEl){
                    countEl.textContent = matched.length + ' مشروع';
                    countEl.classList.add('show');
                }
                if(noResultsEl) noResultsEl.classList.remove('show');
                grid.style.display = '';
            } else {
                if(countEl) countEl.classList.remove('show');
                if(noResultsEl) noResultsEl.classList.add('show');
                grid.style.display = 'none';
            }
        }

        setTimeout(fixSearch, 800);
        setTimeout(fixSearch, 2000);
    })();
    </script>
    <?php
}












// ========== 46. ترقيم المشاريع - رقم ثابت فوق الصورة ==========

/* ─── أ) إعادة ترقيم جميع المشاريع من 01 (مرة واحدة) ─── */
add_action('init', 'pdp_renumber_all_projects', 99);
function pdp_renumber_all_projects() {
    // غيّر v5 لـ v6 لو عايز تعيد الترقيم تاني في المستقبل
    if (get_option('pdp_nums_done_v5')) return;

    global $wpdb;

    // جيب كل المشاريع المنشورة مرتبة بتاريخ النشر (الأقدم الأول)
    $projects = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'project' AND post_status = 'publish'
        ORDER BY post_date ASC
    ");

    if (empty($projects)) {
        update_option('pdp_nums_done_v5', '1');
        return;
    }

    // امسح كل الأرقام القديمة الأول
    $wpdb->delete($wpdb->postmeta, ['meta_key' => 'project_number']);

    // رقّم من 1
    $num = 1;
    foreach ($projects as $pid) {
        update_post_meta($pid, 'project_number', $num);
        $num++;
    }

    // احفظ آخر رقم اتاستخدم
    update_option('pdp_num_counter', $num - 1);
    update_option('pdp_nums_done_v5', '1');
}

/* ─── ب) ترقيم المشاريع الجديدة تلقائيًا ─── */
add_action('transition_post_status', 'pdp_give_number', 10, 3);
function pdp_give_number($new_status, $old_status, $post) {
    if ($post->post_type !== 'project' || $new_status !== 'publish') return;

    // لو المشروع عنده رقم بالفعل، ما تعملش حاجة
    $existing = get_post_meta($post->ID, 'project_number', true);
    if (!empty($existing) && (int)$existing > 0) return;

    // خد آخر رقم من الـ counter وزوّده 1
    $counter = (int) get_option('pdp_num_counter', 0);
    $next = $counter + 1;

    update_post_meta($post->ID, 'project_number', $next);
    update_option('pdp_num_counter', $next);
}

/* ─── ج) عرض الأرقام على كروت المشاريع ─── */
add_action('wp_footer', 'pdp_render_numbers', 991);
function pdp_render_numbers() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;

    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT p.post_title, pm.meta_value as num
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'project_number'
          AND p.post_type = 'project'
          AND p.post_status = 'publish'
          AND pm.meta_value != ''
          AND pm.meta_value != '0'
    ");

    $map = [];
    $total = wp_count_posts('project')->publish;
    $digits = strlen((string)$total);
    if ($digits < 2) $digits = 2;

    foreach ($rows as $r) {
        $map[$r->post_title] = str_pad((int)$r->num, $digits, '0', STR_PAD_LEFT);
    }
    if (empty($map)) return;
    ?>
    <style>
    .pdp-pn{position:absolute;bottom:14px;right:14px;z-index:20;display:flex;align-items:center;background:rgba(15,23,42,.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);padding:8px 16px;border-radius:10px;border:1.5px solid rgba(212,168,83,.4);box-shadow:0 4px 18px rgba(0,0,0,.35);transition:all .3s;pointer-events:none;gap:3px}
    .pdp-project-card:hover .pdp-pn{background:rgba(15,23,42,.95);border-color:rgba(212,168,83,.75);transform:scale(1.1);box-shadow:0 8px 30px rgba(212,168,83,.3)}
    .pdp-pn-h{color:#d4a853;font-weight:900;font-size:.9rem;font-family:'Courier New',monospace}
    .pdp-pn-v{color:#fff;font-weight:900;font-size:1rem;font-family:'Courier New',monospace;letter-spacing:2px}
    .pdp-project-card.pdp-featured .pdp-pn{right:auto;left:14px}
    @media(max-width:768px){.pdp-pn{padding:6px 13px;bottom:10px;right:10px;border-radius:8px}.pdp-pn-h{font-size:.82rem}.pdp-pn-v{font-size:.9rem;letter-spacing:1.5px}.pdp-project-card.pdp-featured .pdp-pn{left:10px}}
    </style>
    <script>
    (function(){
        var m=<?php echo json_encode($map, JSON_UNESCAPED_UNICODE); ?>;
        function go(){
            document.querySelectorAll('.pdp-project-card').forEach(function(c){
                if(c.querySelector('.pdp-pn'))return;
                var t=c.querySelector('.pdp-card-title');
                if(!t)return;
                var n=m[t.textContent.trim()];
                if(!n)return;
                var img=c.querySelector('.pdp-card-image');
                if(!img)return;
                var d=document.createElement('div');
                d.className='pdp-pn';
                d.innerHTML='<span class="pdp-pn-h">#</span><span class="pdp-pn-v">'+n+'</span>';
                img.appendChild(d);
            });
        }
        setTimeout(go,300);setTimeout(go,1000);setTimeout(go,2500);
        if(typeof jQuery!=='undefined')jQuery(document).ajaxComplete(function(){setTimeout(go,400)});
        var g=document.querySelector('[id^="pdpProjectsGrid"]');
        if(g)new MutationObserver(function(){setTimeout(go,200)}).observe(g,{childList:true});
    })();
    </script>
    <?php
}

/* ─── د) عرض الرقم في صفحة المشروع الواحد ─── */
add_action('wp_footer', 'pdp_render_number_single', 991);
function pdp_render_number_single() {
    if (!is_singular('project')) return;
    $n = get_post_meta(get_the_ID(), 'project_number', true);
    if (!$n) return;

    $total = wp_count_posts('project')->publish;
    $digits = strlen((string)$total);
    if ($digits < 2) $digits = 2;
    $num = str_pad((int)$n, $digits, '0', STR_PAD_LEFT);
    ?>
    <style>
    .pds-pn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,rgba(29,45,81,.06),rgba(29,45,81,.02));border:2px solid rgba(29,45,81,.12);padding:8px 18px;border-radius:10px;margin-bottom:14px;transition:all .3s}
    .pds-pn:hover{border-color:rgba(212,168,83,.4);background:linear-gradient(135deg,rgba(212,168,83,.08),rgba(212,168,83,.03))}
    .pds-pn-l{font-size:.85rem;font-weight:600;color:#64748b}
    .pds-pn-v{background:linear-gradient(135deg,#1D2D51,#2a4a7f);color:#d4a853;padding:5px 16px;border-radius:8px;font-weight:900;font-size:1.05rem;font-family:'Courier New',monospace;letter-spacing:2px;box-shadow:0 2px 8px rgba(29,45,81,.2)}
    </style>
    <script>
    (function(){
        function go(){
            var t=document.querySelector('.pds-title');
            if(!t||document.querySelector('.pds-pn'))return;
            var isEn=new URLSearchParams(window.location.search).get('lang')==='en';
            var el=document.createElement('div');
            el.className='pds-pn';
            el.innerHTML='<span class="pds-pn-l">'+(isEn?'Project No.':'رقم المشروع')+'</span><span class="pds-pn-v">#<?php echo esc_js($num); ?></span>';
            t.parentNode.insertBefore(el,t);
        }
        setTimeout(go,100);
    })();
    </script>
    <?php
}

/* ─── هـ) عمود الرقم في لوحة التحكم ─── */
add_filter('manage_project_posts_columns', 'pdp_numcol', 20);
function pdp_numcol($cols) {
    $new = [];
    foreach ($cols as $k => $v) {
        if ($k === 'title') $new['pn'] = '🔢';
        $new[$k] = $v;
    }
    return $new;
}

add_action('manage_project_posts_custom_column', 'pdp_numcol_val', 10, 2);
function pdp_numcol_val($col, $pid) {
    if ($col !== 'pn') return;
    $n = get_post_meta($pid, 'project_number', true);
    echo $n
        ? '<span style="background:#1D2D51;color:#d4a853;padding:4px 10px;border-radius:6px;font-weight:900;font-size:.85rem;font-family:monospace">#' . str_pad((int)$n, 4, '0', STR_PAD_LEFT) . '</span>'
        : '<span style="color:#ccc">—</span>';
}

add_filter('manage_edit-project_sortable_columns', 'pdp_numsort', 20);
function pdp_numsort($c) { $c['pn'] = 'pn'; return $c; }

add_action('pre_get_posts', 'pdp_numob', 20);
function pdp_numob($q) {
    if (!is_admin() || !$q->is_main_query() || $q->get('orderby') !== 'pn') return;
    $q->set('meta_key', 'project_number');
    $q->set('orderby', 'meta_value_num');
}






// ========== 47. إرسال إيميل احترافي للمؤسس عند نشر المشروع (نسخة نهائية) ==========

// نستخدم هوكين مع بعض لضمان الاشتغال في كل حالات النشر
add_action('publish_project', 'pdp_trigger_welcome_email', 20, 2);
add_action('save_post_project', 'pdp_trigger_welcome_email_save', 30, 3);

// هوك النشر المباشر
function pdp_trigger_welcome_email($pid, $post) {
    pdp_maybe_send_welcome($pid, 'publish_project');
}

// هوك الحفظ - احتياطي
function pdp_trigger_welcome_email_save($pid, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_status !== 'publish') return;
    pdp_maybe_send_welcome($pid, 'save_post_project');
}

// الدالة اللي بتقرر تبعت ولا لأ
function pdp_maybe_send_welcome($pid, $source) {
    if (get_post_type($pid) !== 'project') return;
    if (get_post_status($pid) !== 'publish') return;

    // نمنع التكرار
    if (get_post_meta($pid, '_pdp_welcome_email_sent', true) === '1') {
        error_log("PDP EMAIL [$source]: المشروع #$pid اتبعتله قبل كده - تخطّي");
        return;
    }

    $result = pdp_do_send_welcome_email($pid);
    error_log("PDP EMAIL [$source]: المشروع #$pid - " . ($result['sent'] ? 'نجح ✅' : 'فشل ❌') . " - " . $result['reason']);
}

/**
 * الدالة الأساسية للإرسال - بترجّع تفاصيل النتيجة عشان نقدر نشخّص
 */
function pdp_do_send_welcome_email($pid) {

    $email_raw = get_post_meta($pid, 'email', true);
    $email = is_email($email_raw);

    if (!$email) {
        return ['sent' => false, 'reason' => "مفيش إيميل صالح. القيمة المسجلة: '" . esc_html($email_raw) . "'", 'email' => $email_raw];
    }

    $founder    = get_post_meta($pid, 'founder_name', true);
    $founder_en = get_post_meta($pid, 'founder_name_en', true);
    $company    = get_post_meta($pid, 'company_name', true);
    $title_ar   = get_the_title($pid);
    $project_url = get_permalink($pid);
    $founder_display = $founder ?: ($founder_en ?: ($company ?: 'صاحب المشروع'));

    $project_img = '';
    if (has_post_thumbnail($pid)) {
        $project_img = get_the_post_thumbnail_url($pid, 'large');
    } else {
        $gallery = get_post_meta($pid, 'project_gallery', true);
        if ($gallery) {
            $ids = array_filter(explode(',', $gallery));
            if (!empty($ids[0])) $project_img = wp_get_attachment_image_url(intval($ids[0]), 'large');
        }
    }
    if ($project_img) $project_img = set_url_scheme($project_img, 'https');

    $project_number = get_post_meta($pid, 'project_number', true);
    $number_badge = $project_number ? '#' . str_pad((int)$project_number, 4, '0', STR_PAD_LEFT) : '';

    $logo_pb    = 'https://vibesholding.com/wp-content/uploads/2026/02/pb-png.webp';
    $logo_badge = 'https://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp';
    $vibes_url     = 'https://vibesholding.com/';
    $investors_url = 'https://vcmem.com/investors-club/';

    $subject = '🎉 مبروك! تم ادراج مشروعك في بنك المشاريع - فايبز القابضة';

    ob_start();
    ?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo esc_html($subject); ?></title></head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:30px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 45px rgba(29,45,81,.15);">
  <tr><td style="background:linear-gradient(135deg,#1D2D51 0%,#2a4a7f 100%);padding:40px 30px 30px;text-align:center;">
    <img src="<?php echo esc_url($logo_pb); ?>" alt="بنك المشاريع" width="110" style="display:inline-block;max-width:110px;height:auto;margin-bottom:18px;">
    <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:900;line-height:1.4;">🎉 تهانينا!</h1>
    <p style="margin:10px 0 0;color:#d4a853;font-size:16px;font-weight:700;">تم ادراج مشروعك بنجاح في بنك المشاريع</p>
  </td></tr>
  <tr><td style="height:5px;background:linear-gradient(90deg,#b8923d,#d4a853,#f0d78c,#d4a853,#b8923d);"></td></tr>
  <tr><td style="padding:35px 35px 25px;">
    <p style="margin:0 0 18px;color:#1D2D51;font-size:18px;font-weight:800;">أهلاً <?php echo esc_html($founder_display); ?> 👋</p>
    <p style="margin:0 0 22px;color:#475569;font-size:15px;line-height:1.9;">يسعدنا أن نعلمك بأنه <strong style="color:#1D2D51;">تمت مراجعة وادراج مشروعك</strong> وعرضه رسمياً ضمن <strong style="color:#b8923d;">بنك المشاريع</strong> من <a href="<?php echo esc_url($vibes_url); ?>" style="color:#1D2D51;font-weight:700;text-decoration:none;border-bottom:2px solid #d4a853;">فايبز القابضة</a>. مشروعك أصبح الآن مرئياً أمام آلاف الشركاء المحتملين✨</p>
    
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;margin:0 0 24px;">
      <tr>
        <td style="padding:18px 20px;color:#475569;font-size:14px;line-height:1.9;">
          <strong style="color:#1D2D51;">📌 تنويه مهم:</strong>
          إدراج مشروعك في بنك المشاريع لا يُعد ضمانًا للحصول على شركاء؛ حيث يعتمد ذلك على جودة المشروع، وقوة العرض التقديمي، وجاذبية الفرصة، ومدى توافقها مع اهتمامات الشركاء المحتملين، وسيتم التواصل معك في حال جهوزية مشروعك للطرح في إحدى ملتقيات نادي المستثمرين.
        </td>
      </tr>
    </table>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(145deg,#fffef7,#fefcf0);border:2px solid #d4a853;border-radius:16px;overflow:hidden;margin-bottom:28px;">
      <?php if ($project_img): ?><tr><td><img src="<?php echo esc_url($project_img); ?>" alt="<?php echo esc_attr($title_ar); ?>" width="600" style="display:block;width:100%;height:auto;max-height:260px;object-fit:cover;"></td></tr><?php endif; ?>
      <tr><td style="padding:22px 25px;">
        <?php if ($number_badge): ?><span style="display:inline-block;background:#1D2D51;color:#d4a853;padding:4px 14px;border-radius:8px;font-weight:900;font-size:13px;font-family:'Courier New',monospace;margin-bottom:12px;"><?php echo esc_html($number_badge); ?></span><?php endif; ?>
        <h2 style="margin:0 0 8px;color:#1D2D51;font-size:20px;font-weight:900;line-height:1.4;"><?php echo esc_html($title_ar); ?></h2>
        <?php if ($company): ?><p style="margin:0;color:#71788B;font-size:14px;font-weight:600;">🏢 <?php echo esc_html($company); ?></p><?php endif; ?>
      </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding-bottom:10px;">
      <a href="<?php echo esc_url($project_url); ?>" style="display:inline-block;background:linear-gradient(135deg,#d4a853,#f0d78c);color:#1D2D51;text-decoration:none;font-size:16px;font-weight:900;padding:16px 46px;border-radius:50px;box-shadow:0 8px 25px rgba(212,168,83,.4);">🔗 عرض مشروعك الآن</a>
    </td></tr></table>

    <p style="margin:18px 0 0;text-align:center;color:#94a3b8;font-size:12px;line-height:1.6;">أو انسخ الرابط:<br><a href="<?php echo esc_url($project_url); ?>" style="color:#1D2D51;font-size:12px;word-break:break-all;text-decoration:underline;"><?php echo esc_html($project_url); ?></a></p>
  </td></tr>
  <tr><td style="background:linear-gradient(135deg,#1D2D51,#0f1829);padding:30px;text-align:center;">
    <img src="<?php echo esc_url($logo_badge); ?>" alt="فايبز القابضة" width="48" style="display:inline-block;width:48px;height:48px;border-radius:50%;margin-bottom:12px;">
    <p style="margin:0 0 4px;color:#ffffff;font-size:15px;font-weight:800;">فايبز القابضة</p>
    <p style="margin:0 0 16px;color:#94a3b8;font-size:12px;">منصة بنك المشاريع — حيث تلتقي الفرص بالمستثمرين</p>
    <a href="<?php echo esc_url($vibes_url); ?>" style="margin:0 6px;color:#d4a853;font-size:12px;font-weight:700;text-decoration:none;">🌐 الموقع</a><span style="color:#475569;">|</span><a href="<?php echo esc_url($investors_url); ?>" style="margin:0 6px;color:#d4a853;font-size:12px;font-weight:700;text-decoration:none;">💼 نادي المستثمرين</a>
    <p style="margin:18px 0 0;color:#475569;font-size:11px;">© <?php echo date('Y'); ?> Vibes Holding. جميع الحقوق محفوظة.</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
    <?php
    $message = ob_get_clean();

    $site_name  = get_bloginfo('name') ?: 'بنك المشاريع';
    $from_email = 'no-reply@' . preg_replace('/^www\./', '', parse_url(home_url(), PHP_URL_HOST));
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $from_email . '>',
    ];

    $sent = wp_mail($email, $subject, $message, $headers);

    if ($sent) {
        update_post_meta($pid, '_pdp_welcome_email_sent', '1');
        update_post_meta($pid, '_pdp_welcome_email_date', current_time('mysql'));
        return ['sent' => true, 'reason' => "اتبعت بنجاح لـ $email", 'email' => $email];
    }

    return ['sent' => false, 'reason' => "wp_mail رجّع false - السيرفر مش قادر يبعت (محتاج SMTP)", 'email' => $email];
}

/**
 * صفحة اختبار في الأدمن - تبعت إيميل فوري وتقولك النتيجة
 */
add_action('admin_menu', function() {
    add_submenu_page('edit.php?post_type=project', 'اختبار الإيميل', '🧪 اختبار الإيميل', 'manage_options', 'pdp-email-test', 'pdp_email_test_page');
});

function pdp_email_test_page() {
    echo '<div class="wrap"><h1>🧪 اختبار إرسال الإيميل</h1>';

    // اختبار 1: wp_mail شغال أصلاً؟
    if (isset($_POST['pdp_test_basic']) && check_admin_referer('pdp_email_test')) {
        $to = sanitize_email($_POST['test_email']);
        $ok = wp_mail($to, 'اختبار بنك المشاريع', 'لو وصلتك الرسالة دي يبقى wp_mail شغال. ✅', ['Content-Type: text/html; charset=UTF-8']);
        echo $ok
            ? '<div class="notice notice-success"><p>✅ wp_mail رجّع <b>true</b> - الرسالة اتسلّمت للسيرفر. شوف بريدك (و Spam).</p></div>'
            : '<div class="notice notice-error"><p>❌ wp_mail رجّع <b>false</b> - السيرفر مش قادر يبعت إيميلات أصلاً. محتاج بلجن SMTP.</p></div>';
    }

    // اختبار 2: إيميل المشروع الكامل
    if (isset($_POST['pdp_test_project']) && check_admin_referer('pdp_email_test')) {
        $pid = intval($_POST['test_pid']);
        delete_post_meta($pid, '_pdp_welcome_email_sent');
        $r = pdp_do_send_welcome_email($pid);
        $cls = $r['sent'] ? 'notice-success' : 'notice-error';
        echo "<div class='notice $cls'><p>" . ($r['sent'] ? '✅' : '❌') . " المشروع #$pid: <b>" . esc_html($r['reason']) . "</b></p></div>";
    }
    ?>
    <div style="background:#fff;padding:25px;border-radius:10px;border:1px solid #e2e8f0;max-width:600px;margin-top:20px;">
        <h2>1️⃣ اختبار أساسي: هل wp_mail شغال؟</h2>
        <form method="post">
            <?php wp_nonce_field('pdp_email_test'); ?>
            <input type="email" name="test_email" placeholder="اكتب إيميلك للاختبار" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" style="width:300px;padding:8px;" required>
            <button type="submit" name="pdp_test_basic" class="button button-primary">إرسال اختبار</button>
        </form>
        <hr style="margin:25px 0;">
        <h2>2️⃣ اختبار إيميل مشروع كامل</h2>
        <form method="post">
            <?php wp_nonce_field('pdp_email_test'); ?>
            <label>رقم المشروع (ID): </label>
            <input type="number" name="test_pid" placeholder="مثال: 123" style="width:120px;padding:8px;" required>
            <button type="submit" name="pdp_test_project" class="button button-primary">إرسال إيميل المشروع</button>
            <p style="color:#64748b;font-size:13px;">ملاحظة: لازم يكون فيه إيميل مسجّل في حقل المشروع. الـ ID بتلاقيه في رابط تعديل المشروع (post=123).</p>
        </form>
    </div>
    </div>
    <?php
}












// ========== 35+46 (نسخة موحدة نظيفة) ==========

/* ─── أ) إضافة الشورت كود تلقائياً عند نشر/حفظ أي مشروع ─── */
add_action('save_post_project', 'pdp_inject_shortcode_on_save', 5, 3);
function pdp_inject_shortcode_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (get_post_type($post_id) !== 'project') return;
    if (!current_user_can('edit_post', $post_id)) return;

    $content = $post->post_content;

    // لو الشورت كود موجود بالفعل، بلاش تكرار
    if (strpos($content, '[projects_directory') !== false) return;

    $shortcode = '[projects_directory columns="4"]';
    $new_content = trim($content) === '' ? $shortcode : $content . "\n\n" . $shortcode;

    // نشيل الهوك مؤقتاً عشان نتجنب لووب لا نهائي
    remove_action('save_post_project', 'pdp_inject_shortcode_on_save', 5);
    wp_update_post(['ID' => $post_id, 'post_content' => $new_content]);
    add_action('save_post_project', 'pdp_inject_shortcode_on_save', 5, 3);
}


/* ─── ب) إعطاء رقم تلقائي لأي مشروع جديد ─── */
add_action('transition_post_status', 'pdp_assign_number_new', 10, 3);
function pdp_assign_number_new($new_status, $old_status, $post) {
    if ($post->post_type !== 'project' || $new_status !== 'publish') return;
    $existing = get_post_meta($post->ID, 'project_number', true);
    if (!empty($existing) && (int)$existing > 0) return;

    $next = pdp_get_next_number();
    update_post_meta($post->ID, 'project_number', $next);
}

/* الرقم التالي = أكبر رقم مستخدم فعلياً + 1 (مش من counter قديم) */
function pdp_get_next_number() {
    global $wpdb;
    $max = (int) $wpdb->get_var("
        SELECT MAX(CAST(pm.meta_value AS UNSIGNED))
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'project_number'
          AND p.post_type = 'project'
          AND p.post_status = 'publish'
    ");
    return $max + 1;
}


/* ─── ج) إصلاح المشاريع الناقصة رقم تلقائياً عند فتح صفحة المشاريع ─── */
add_action('admin_init', 'pdp_autofix_missing_numbers');
function pdp_autofix_missing_numbers() {
    global $pagenow;
    if ($pagenow !== 'edit.php' || ($_GET['post_type'] ?? '') !== 'project') return;

    global $wpdb;
    $missing = $wpdb->get_col("
        SELECT p.ID FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm
            ON p.ID = pm.post_id AND pm.meta_key = 'project_number'
        WHERE p.post_type = 'project' AND p.post_status = 'publish'
          AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = '0')
        ORDER BY p.post_date ASC
    ");
    if (empty($missing)) return;

    foreach ($missing as $pid) {
        update_post_meta($pid, 'project_number', pdp_get_next_number());
    }
}


/* ─── د) عرض الأرقام على الكروت ─── */
add_action('wp_footer', 'pdp_show_numbers_cards', 991);
function pdp_show_numbers_cards() {
    global $post;
    if (!$post) return;
    if (!has_shortcode($post->post_content, 'projects_directory') && !has_shortcode($post->post_content, 'projects_directory_en')) return;

    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT p.post_title, pm.meta_value AS num
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'project_number'
          AND p.post_type = 'project' AND p.post_status = 'publish'
          AND pm.meta_value != '' AND pm.meta_value != '0'
    ");

    $total = (int) wp_count_posts('project')->publish;
    $digits = max(2, strlen((string)$total));
    $map = [];
    foreach ($rows as $r) $map[$r->post_title] = str_pad((int)$r->num, $digits, '0', STR_PAD_LEFT);
    if (empty($map)) return;
    ?>
    <style>
    .pdp-pn{position:absolute;bottom:14px;right:14px;z-index:20;display:flex;align-items:center;background:rgba(15,23,42,.85);backdrop-filter:blur(12px);padding:8px 16px;border-radius:10px;border:1.5px solid rgba(212,168,83,.4);box-shadow:0 4px 18px rgba(0,0,0,.35);transition:all .3s;pointer-events:none;gap:3px}
    .pdp-project-card:hover .pdp-pn{background:rgba(15,23,42,.95);border-color:rgba(212,168,83,.75);transform:scale(1.1)}
    .pdp-pn-h{color:#d4a853;font-weight:900;font-size:.9rem;font-family:'Courier New',monospace}
    .pdp-pn-v{color:#fff;font-weight:900;font-size:1rem;font-family:'Courier New',monospace;letter-spacing:2px}
    .pdp-project-card.pdp-featured .pdp-pn{right:auto;left:14px}
    @media(max-width:768px){.pdp-pn{padding:6px 13px;bottom:10px;right:10px}.pdp-pn-h{font-size:.82rem}.pdp-pn-v{font-size:.9rem;letter-spacing:1.5px}.pdp-project-card.pdp-featured .pdp-pn{left:10px}}
    </style>
    <script>
    (function(){
        var m=<?php echo json_encode($map, JSON_UNESCAPED_UNICODE); ?>;
        function go(){
            document.querySelectorAll('.pdp-project-card').forEach(function(c){
                if(c.querySelector('.pdp-pn'))return;
                var t=c.querySelector('.pdp-card-title');if(!t)return;
                var n=m[t.textContent.trim()];if(!n)return;
                var img=c.querySelector('.pdp-card-image');if(!img)return;
                var d=document.createElement('div');d.className='pdp-pn';
                d.innerHTML='<span class="pdp-pn-h">#</span><span class="pdp-pn-v">'+n+'</span>';
                img.appendChild(d);
            });
        }
        setTimeout(go,300);setTimeout(go,1000);setTimeout(go,2500);
        if(typeof jQuery!=='undefined')jQuery(document).ajaxComplete(function(){setTimeout(go,400)});
        var g=document.querySelector('[id^="pdpProjectsGrid"]');
        if(g)new MutationObserver(function(){setTimeout(go,200)}).observe(g,{childList:true});
    })();
    </script>
    <?php
}

/* ─── هـ) الرقم في صفحة المشروع الواحد ─── */
add_action('wp_footer', 'pdp_show_number_single', 991);
function pdp_show_number_single() {
    if (!is_singular('project')) return;
    $n = get_post_meta(get_the_ID(), 'project_number', true);
    if (!$n) return;
    $total = (int) wp_count_posts('project')->publish;
    $digits = max(2, strlen((string)$total));
    $num = str_pad((int)$n, $digits, '0', STR_PAD_LEFT);
    ?>
    <style>
    .pds-pn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,rgba(29,45,81,.06),rgba(29,45,81,.02));border:2px solid rgba(29,45,81,.12);padding:8px 18px;border-radius:10px;margin-bottom:14px}
    .pds-pn-l{font-size:.85rem;font-weight:600;color:#64748b}
    .pds-pn-v{background:linear-gradient(135deg,#1D2D51,#2a4a7f);color:#d4a853;padding:5px 16px;border-radius:8px;font-weight:900;font-size:1.05rem;font-family:'Courier New',monospace;letter-spacing:2px}
    </style>
    <script>
    (function(){
        function go(){
            var t=document.querySelector('.pds-title');
            if(!t||document.querySelector('.pds-pn'))return;
            var isEn=new URLSearchParams(window.location.search).get('lang')==='en';
            var el=document.createElement('div');el.className='pds-pn';
            el.innerHTML='<span class="pds-pn-l">'+(isEn?'Project No.':'رقم المشروع')+'</span><span class="pds-pn-v">#<?php echo esc_js($num); ?></span>';
            t.parentNode.insertBefore(el,t);
        }
        setTimeout(go,100);
    })();
    </script>
    <?php
}

/* ─── و) عمود الرقم في لوحة التحكم ─── */
add_filter('manage_project_posts_columns', 'pdp_numcol_new', 20);
function pdp_numcol_new($cols) {
    $new = [];
    foreach ($cols as $k => $v) { if ($k === 'title') $new['pn'] = '🔢'; $new[$k] = $v; }
    return $new;
}
add_action('manage_project_posts_custom_column', 'pdp_numcol_val_new', 10, 2);
function pdp_numcol_val_new($col, $pid) {
    if ($col !== 'pn') return;
    $n = get_post_meta($pid, 'project_number', true);
    echo $n
        ? '<span style="background:#1D2D51;color:#d4a853;padding:4px 10px;border-radius:6px;font-weight:900;font-size:.85rem;font-family:monospace">#' . str_pad((int)$n, 4, '0', STR_PAD_LEFT) . '</span>'
        : '<span style="color:#ccc">—</span>';
}
add_filter('manage_edit-project_sortable_columns', 'pdp_numsort_new', 20);
function pdp_numsort_new($c) { $c['pn'] = 'pn'; return $c; }
add_action('pre_get_posts', 'pdp_numob_new', 20);
function pdp_numob_new($q) {
    if (!is_admin() || !$q->is_main_query() || $q->get('orderby') !== 'pn') return;
    $q->set('meta_key', 'project_number');
    $q->set('orderby', 'meta_value_num');
}


/* ─── ز) صفحة إصلاح الترقيم (إعادة ترقيم كامل من 01) ─── */
add_action('admin_menu', function() {
    add_submenu_page('edit.php?post_type=project', 'إصلاح الترقيم', '🔢 إصلاح الترقيم', 'manage_options', 'pdp-fix-numbers', 'pdp_fix_numbers_page_new');
});
function pdp_fix_numbers_page_new() {
    global $wpdb;

    if (isset($_POST['pdp_renumber_now']) && check_admin_referer('pdp_renumber')) {
        $projects = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'project' AND post_status = 'publish'
            ORDER BY post_date ASC
        ");
        $wpdb->delete($wpdb->postmeta, ['meta_key' => 'project_number']);
        $num = 0;
        foreach ($projects as $pid) { $num++; update_post_meta($pid, 'project_number', $num); }
        delete_option('pdp_num_counter'); // مش محتاجينه خلاص
        echo '<div class="notice notice-success"><p>✅ تم إعادة ترقيم <b>' . $num . '</b> مشروع من 01 إلى ' . str_pad($num, 4, '0', STR_PAD_LEFT) . '</p></div>';
    }

    $total = (int) wp_count_posts('project')->publish;
    $numbered = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type='project' AND p.post_status='publish'
          AND pm.meta_key='project_number' AND pm.meta_value!='' AND pm.meta_value!='0'
    ");
    $max_num = (int) $wpdb->get_var("
        SELECT MAX(CAST(pm.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id=p.ID
        WHERE pm.meta_key='project_number' AND p.post_type='project' AND p.post_status='publish'
    ");
    $missing = $total - $numbered;
    ?>
    <div class="wrap">
        <h1>🔢 إصلاح ترقيم المشاريع</h1>
        <div style="background:#fff;padding:25px;border-radius:12px;border:1px solid #e2e8f0;max-width:650px;margin-top:20px;">
            <table class="widefat" style="margin-bottom:20px;">
                <tr><td style="font-weight:600;">إجمالي المشاريع</td><td><b style="color:#1D2D51;"><?php echo $total; ?></b></td></tr>
                <tr><td style="font-weight:600;">المرقّمة</td><td><b style="color:#10b981;"><?php echo $numbered; ?></b></td></tr>
                <tr><td style="font-weight:600;">بدون رقم</td><td><b style="color:<?php echo $missing>0?'#ef4444':'#10b981';?>;"><?php echo $missing; ?></b></td></tr>
                <tr><td style="font-weight:600;">أكبر رقم مستخدم</td><td><b style="color:#b8923d;">#<?php echo str_pad($max_num,4,'0',STR_PAD_LEFT); ?></b></td></tr>
            </table>
            <?php if ($missing > 0 || $max_num != $total): ?>
                <div style="background:#fef9e7;border:2px solid #d4a853;border-radius:8px;padding:15px;margin-bottom:20px;">⚠️ في عدم تطابق (أكبر رقم = <?php echo $max_num; ?> لكن العدد الفعلي = <?php echo $total; ?>). اضغط الزر لإصلاحه.</div>
            <?php else: ?>
                <div style="background:#ecfdf5;border:2px solid #10b981;border-radius:8px;padding:15px;margin-bottom:20px;">✅ الترقيم سليم ومتطابق.</div>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('سيتم إعادة ترقيم كل المشاريع من 01 حسب تاريخ النشر. متأكد؟');">
                <?php wp_nonce_field('pdp_renumber'); ?>
                <button type="submit" name="pdp_renumber_now" class="button button-primary button-large">🔄 إعادة ترقيم كل المشاريع من 01</button>
            </form>
        </div>
    </div>
    <?php
}




/* ========= إضافة بلوك الكود القصير تلقائياً عند إنشاء مشروع جديد ========= */
add_filter('default_content', 'pdp_default_shortcode_block', 10, 2);
function pdp_default_shortcode_block($content, $post) {
    // اشتغل فقط مع نوع المنشور "project"
    if ($post->post_type === 'project') {
        $content = '<!-- wp:shortcode -->[projects_directory columns="4"]<!-- /wp:shortcode -->';
    }
    return $content;
}


// ========== 48. إرسال إيميل مخصص لصاحب المشروع من لوحة التحكم ==========

add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=project',
        'إرسال إيميل لصاحب المشروع',
        '✉️ إرسال إيميل',
        'manage_options',
        'pdp-send-custom-email',
        'pdp_send_custom_email_page'
    );
});

function pdp_send_custom_email_page() {
    // معالجة الإرسال
    if (isset($_POST['pdp_send_custom']) && check_admin_referer('pdp_send_custom_email')) {
        $pid          = intval($_POST['custom_pid']);
        $custom_msg   = wp_kses_post($_POST['custom_message']);
        $custom_subj  = sanitize_text_field($_POST['custom_subject']);

        $result = pdp_do_send_custom_email($pid, $custom_subj, $custom_msg);
        $cls = $result['sent'] ? 'notice-success' : 'notice-error';
        echo "<div class='notice $cls'><p>" . ($result['sent'] ? '✅' : '❌') . ' ' . esc_html($result['reason']) . "</p></div>";
    }

    // جلب كل المشاريع اللي عندها إيميل
    $projects = get_posts([
        'post_type'      => 'project',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    // قيمة افتراضية للرسالة
    $default_message = "يسعدنا أن نعلمك بأنه تمت مراجعة مشروعك واعتماده رسمياً، وأصبح الآن معروضاً ضمن بنك المشاريع من فايبز القابضة.\n\nمشروعك أصبح مرئياً أمام آلاف المستثمرين ورجال الأعمال، ونتمنى لك التوفيق في إيجاد الشريك المناسب.\n\nفريق فايبز القابضة يقف معك في كل خطوة.";
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">✉️ إرسال إيميل مخصص لصاحب المشروع</h1>
        <p style="color:#64748b;font-size:14px;">اكتب رسالتك بنفسك وابعتها لصاحب أي مشروع بنفس تصميم الإيميل الاحترافي.</p>

        <div style="background:#fff;padding:30px;border-radius:12px;border:1px solid #e2e8f0;max-width:750px;margin-top:20px;box-shadow:0 2px 15px rgba(0,0,0,.05);">
            <form method="post">
                <?php wp_nonce_field('pdp_send_custom_email'); ?>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:700;margin-bottom:8px;color:#1D2D51;">اختر المشروع</label>
                    <select name="custom_pid" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;">
                        <option value="">— اختر مشروعاً —</option>
                        <?php foreach ($projects as $p):
                            $em = get_post_meta($p->ID, 'email', true);
                            $founder = get_post_meta($p->ID, 'founder_name', true);
                            $label = '#' . $p->ID . ' — ' . $p->post_title;
                            if ($founder) $label .= ' (' . $founder . ')';
                            $label .= $em ? ' ✅' : ' ❌ بدون إيميل';
                        ?>
                            <option value="<?php echo $p->ID; ?>" <?php echo $em ? '' : 'disabled'; ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p style="color:#94a3b8;font-size:12px;margin-top:5px;">المشاريع اللي بدون إيميل مش هتقدر تختارها.</p>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:700;margin-bottom:8px;color:#1D2D51;">عنوان الرسالة (Subject)</label>
                    <input type="text" name="custom_subject" value="🎉 رسالة من فايبز القابضة بخصوص مشروعك" required style="width:100%;padding:10px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:700;margin-bottom:8px;color:#1D2D51;">نص الرسالة</label>
                    <textarea name="custom_message" rows="10" required style="width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;line-height:1.8;font-family:inherit;"><?php echo esc_textarea($default_message); ?></textarea>
                    <p style="color:#94a3b8;font-size:12px;margin-top:5px;">اكتب رسالتك بشكل طبيعي. كل سطر جديد هيظهر كفقرة منفصلة في الإيميل. (يدعم بعض وسوم HTML البسيطة مثل &lt;strong&gt;)</p>
                </div>

                <button type="submit" name="pdp_send_custom" class="button button-primary button-large">📤 إرسال الإيميل الآن</button>
            </form>
        </div>
    </div>
    <?php
}

/**
 * دالة إرسال الإيميل المخصص - بنفس تصميم إيميل الترحيب
 */
function pdp_do_send_custom_email($pid, $subject, $message_text) {

    if (get_post_type($pid) !== 'project') {
        return ['sent' => false, 'reason' => 'المشروع غير موجود.'];
    }

    $email_raw = get_post_meta($pid, 'email', true);
    $email = is_email($email_raw);

    if (!$email) {
        return ['sent' => false, 'reason' => "مفيش إيميل صالح للمشروع #$pid. القيمة: '" . esc_html($email_raw) . "'"];
    }

    $founder    = get_post_meta($pid, 'founder_name', true);
    $founder_en = get_post_meta($pid, 'founder_name_en', true);
    $company    = get_post_meta($pid, 'company_name', true);
    $title_ar   = get_the_title($pid);
    $project_url = get_permalink($pid);
    $founder_display = $founder ?: ($founder_en ?: ($company ?: 'صاحب المشروع'));

    // صورة المشروع
    $project_img = '';
    if (has_post_thumbnail($pid)) {
        $project_img = get_the_post_thumbnail_url($pid, 'large');
    } else {
        $gallery = get_post_meta($pid, 'project_gallery', true);
        if ($gallery) {
            $ids = array_filter(explode(',', $gallery));
            if (!empty($ids[0])) $project_img = wp_get_attachment_image_url(intval($ids[0]), 'large');
        }
    }
    if ($project_img) $project_img = set_url_scheme($project_img, 'https');

    $project_number = get_post_meta($pid, 'project_number', true);
    $number_badge = $project_number ? '#' . str_pad((int)$project_number, 4, '0', STR_PAD_LEFT) : '';

    $logo_pb    = 'https://vibesholding.com/wp-content/uploads/2026/02/pb-png.webp';
    $logo_badge = 'https://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp';
    $vibes_url     = 'https://vibesholding.com/';
    $investors_url = 'https://vcmem.com/investors-club/';

    // تحويل أسطر الرسالة لفقرات HTML
    $paragraphs = '';
    foreach (preg_split('/\r\n|\r|\n/', trim($message_text)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $paragraphs .= '<p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.9;">' . $line . '</p>';
    }

    ob_start();
    ?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo esc_html($subject); ?></title></head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;direction:rtl;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:30px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 45px rgba(29,45,81,.15);">
  <tr><td style="background:linear-gradient(135deg,#1D2D51 0%,#2a4a7f 100%);padding:40px 30px 30px;text-align:center;">
    <img src="<?php echo esc_url($logo_pb); ?>" alt="بنك المشاريع" width="110" style="display:inline-block;max-width:110px;height:auto;margin-bottom:18px;">
    <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:900;line-height:1.4;">رسالة من فايبز القابضة</h1>
    <p style="margin:10px 0 0;color:#d4a853;font-size:15px;font-weight:700;">بخصوص مشروعك في بنك المشاريع</p>
  </td></tr>
  <tr><td style="height:5px;background:linear-gradient(90deg,#b8923d,#d4a853,#f0d78c,#d4a853,#b8923d);"></td></tr>
  <tr><td style="padding:35px 35px 25px;">
    <p style="margin:0 0 18px;color:#1D2D51;font-size:18px;font-weight:800;">أهلاً <?php echo esc_html($founder_display); ?> 👋</p>

    <?php echo $paragraphs; ?>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(145deg,#fffef7,#fefcf0);border:2px solid #d4a853;border-radius:16px;overflow:hidden;margin:24px 0 28px;">
      <?php if ($project_img): ?><tr><td><img src="<?php echo esc_url($project_img); ?>" alt="<?php echo esc_attr($title_ar); ?>" width="600" style="display:block;width:100%;height:auto;max-height:260px;object-fit:cover;"></td></tr><?php endif; ?>
      <tr><td style="padding:22px 25px;">
        <?php if ($number_badge): ?><span style="display:inline-block;background:#1D2D51;color:#d4a853;padding:4px 14px;border-radius:8px;font-weight:900;font-size:13px;font-family:'Courier New',monospace;margin-bottom:12px;"><?php echo esc_html($number_badge); ?></span><?php endif; ?>
        <h2 style="margin:0 0 8px;color:#1D2D51;font-size:20px;font-weight:900;line-height:1.4;"><?php echo esc_html($title_ar); ?></h2>
        <?php if ($company): ?><p style="margin:0;color:#71788B;font-size:14px;font-weight:600;">🏢 <?php echo esc_html($company); ?></p><?php endif; ?>
      </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding-bottom:10px;">
      <a href="<?php echo esc_url($project_url); ?>" style="display:inline-block;background:linear-gradient(135deg,#d4a853,#f0d78c);color:#1D2D51;text-decoration:none;font-size:16px;font-weight:900;padding:16px 46px;border-radius:50px;box-shadow:0 8px 25px rgba(212,168,83,.4);">🔗 عرض مشروعك الآن</a>
    </td></tr></table>

    <p style="margin:18px 0 0;text-align:center;color:#94a3b8;font-size:12px;line-height:1.6;">أو انسخ الرابط:<br><a href="<?php echo esc_url($project_url); ?>" style="color:#1D2D51;font-size:12px;word-break:break-all;text-decoration:underline;"><?php echo esc_html($project_url); ?></a></p>
  </td></tr>
  <tr><td style="background:linear-gradient(135deg,#1D2D51,#0f1829);padding:30px;text-align:center;">
    <img src="<?php echo esc_url($logo_badge); ?>" alt="فايبز القابضة" width="48" style="display:inline-block;width:48px;height:48px;border-radius:50%;margin-bottom:12px;">
    <p style="margin:0 0 4px;color:#ffffff;font-size:15px;font-weight:800;">فايبز القابضة</p>
    <p style="margin:0 0 16px;color:#94a3b8;font-size:12px;">منصة بنك المشاريع — حيث تلتقي الفرص بالمستثمرين</p>
    <a href="<?php echo esc_url($vibes_url); ?>" style="margin:0 6px;color:#d4a853;font-size:12px;font-weight:700;text-decoration:none;">🌐 الموقع</a><span style="color:#475569;">|</span><a href="<?php echo esc_url($investors_url); ?>" style="margin:0 6px;color:#d4a853;font-size:12px;font-weight:700;text-decoration:none;">💼 نادي المستثمرين</a>
    <p style="margin:18px 0 0;color:#475569;font-size:11px;">© <?php echo date('Y'); ?> Vibes Holding. جميع الحقوق محفوظة.</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>
    <?php
    $html = ob_get_clean();

    $site_name  = get_bloginfo('name') ?: 'بنك المشاريع';
    $from_email = 'no-reply@' . preg_replace('/^www\./', '', parse_url(home_url(), PHP_URL_HOST));
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $from_email . '>',
    ];

    $sent = wp_mail($email, $subject, $html, $headers);

    if ($sent) {
        update_post_meta($pid, '_pdp_custom_email_last', current_time('mysql'));
        return ['sent' => true, 'reason' => "تم إرسال الإيميل بنجاح إلى $email (المشروع: $title_ar)"];
    }

    return ['sent' => false, 'reason' => "فشل الإرسال — wp_mail رجّع false. غالباً محتاج بلجن SMTP."];
}


// ========== نهاية البلجن ==========
