<?php
/**
 * Single Project Template - Fixed v6.2 (Hardened)
 * Only Company + Views in meta grid
 * Modals + Related Slider Fixed for Mobile
 */

get_header();

while (have_posts()): the_post();
    $pid = get_the_ID();

    $lang = (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'ar';
    $is_en = ($lang === 'en');

    // ── View counter (آمن) ──
    $session_key = 'pdp_viewed_' . $pid;
    $views = (int) get_post_meta($pid, 'views_count', true);
    if (!isset($_COOKIE[$session_key])) {
        $views = $views + 1;
        update_post_meta($pid, 'views_count', $views);
        // setcookie فقط لو الهيدر لسه مطلعش
        if (!headers_sent()) {
            setcookie($session_key, '1', time() + 3600, '/');
        }
    }

    $meta = get_post_meta($pid);
    $pitch_id   = isset($meta['pitch_deck'][0]) ? $meta['pitch_deck'][0] : '';
    $pitch_url  = $pitch_id ? wp_get_attachment_url($pitch_id) : '';
    $details    = isset($meta['project_details'][0]) ? $meta['project_details'][0] : '';
    $gallery    = isset($meta['project_gallery'][0]) ? $meta['project_gallery'][0] : '';
    $is_featured= isset($meta['is_featured'][0]) ? $meta['is_featured'][0] : '';
    $company    = isset($meta['company_name'][0]) ? $meta['company_name'][0] : '';
    $founder    = isset($meta['founder_name'][0]) ? $meta['founder_name'][0] : '';
    $whatsapp   = isset($meta['whatsapp'][0]) ? $meta['whatsapp'][0] : '';
    $website_url= isset($meta['website'][0]) ? $meta['website'][0] : '';

    $title_en   = isset($meta['title_en'][0]) ? $meta['title_en'][0] : '';
    $company_en = isset($meta['company_name_en'][0]) ? $meta['company_name_en'][0] : '';
    $founder_en = isset($meta['founder_name_en'][0]) ? $meta['founder_name_en'][0] : '';
    $details_en = isset($meta['project_details_en'][0]) ? $meta['project_details_en'][0] : '';

    $title = ($is_en && $title_en) ? $title_en : get_the_title();
    $comp  = ($is_en && $company_en) ? $company_en : $company;
    $found = ($is_en && $founder_en) ? $founder_en : $founder;
    $desc  = ($is_en && $details_en) ? $details_en : $details;

    $sectors = get_the_terms($pid, 'sector');
    $stages  = get_the_terms($pid, 'project_stage');
    $sector = '';
    $stage  = '';

    if ($sectors && !is_wp_error($sectors) && !empty($sectors[0])) {
        $sector = function_exists('pdp_translate_term') ? pdp_translate_term($sectors[0]->name, 'sector', $lang) : $sectors[0]->name;
    }
    if ($stages && !is_wp_error($stages) && !empty($stages[0])) {
        $stage = function_exists('pdp_translate_term') ? pdp_translate_term($stages[0]->name, 'stage', $lang) : $stages[0]->name;
    }

    $is_mobile = false;
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $is_mobile = (bool) preg_match('/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|webOS/i', $_SERVER['HTTP_USER_AGENT']);
    }

    $gallery_size = $is_mobile ? 'medium_large' : 'large';
    $thumb_size   = 'thumbnail';
    $full_size    = 'large';

    $images       = array();
    $full_images  = array();
    $thumb_images = array();
    $att_ids      = array();

    if (has_post_thumbnail()) {
        $thumb_id = get_post_thumbnail_id($pid);
        $att_ids[]      = $thumb_id;
        $images[]       = wp_get_attachment_image_url($thumb_id, $gallery_size) ?: get_the_post_thumbnail_url($pid, 'large');
        $full_images[]  = wp_get_attachment_image_url($thumb_id, $full_size) ?: get_the_post_thumbnail_url($pid, 'large');
        $thumb_images[] = wp_get_attachment_image_url($thumb_id, $thumb_size) ?: get_the_post_thumbnail_url($pid, 'thumbnail');
    }
    if ($gallery) {
        foreach (array_filter(explode(',', $gallery)) as $id) {
            $id = intval($id);
            if (!$id) continue;
            $url = wp_get_attachment_image_url($id, $gallery_size);
            if (!$url) $url = wp_get_attachment_image_url($id, 'large');
            if ($url && !in_array($url, $images)) {
                $att_ids[]      = $id;
                $images[]       = $url;
                $full_images[]  = wp_get_attachment_image_url($id, $full_size) ?: $url;
                $thumb_images[] = wp_get_attachment_image_url($id, $thumb_size) ?: $url;
            }
        }
    }
    if (empty($images)) {
        $placeholder = 'https://via.placeholder.com/600x600/1D2D51/C9A961?text=' . urlencode($title);
        $images[]       = $placeholder;
        $full_images[]  = $placeholder;
        $thumb_images[] = $placeholder;
    }

    $back_url    = $is_en ? home_url('/pb-eng/') : home_url('/pb/');
    $other_url   = get_permalink($pid) . ($is_en ? '' : '?lang=en');
    $current_url = get_permalink($pid) . ($is_en ? '?lang=en' : '');

    $logo_badge     = 'http://vibesholding.com/wp-content/uploads/2026/02/%D9%81%D8%A7%D9%8A%D8%A8%D8%B2-%D9%81%D9%8A-%D8%A8%D8%B3-copy.webp';
    $logo_investors = 'http://vibesholding.com/wp-content/uploads/2026/02/%D9%84%D9%88%D8%AC%D9%88-%D9%86%D8%A7%D8%AF%D9%8A-%D8%A7%D9%84%D9%85%D8%B3%D8%AA%D8%AB%D9%85%D8%B1%D9%8A%D9%86-1.webp';
    $logo_sp        = 'http://vibesholding.com/wp-content/uploads/2026/02/%D8%B4%D8%B1%D9%83%D8%A7%D8%A1-%D8%A7%D9%84%D9%86%D8%AC%D8%A7%D8%AD-1.webp';

    $t = $is_en ? array(
        'back' => 'Back to Projects',
        'featured' => 'Vibes Featured',
        'company' => 'Company',
        'views' => 'Views',
        'details' => 'Project Details',
        'gallery' => 'Gallery',
        'pitch' => 'Download Pitch Deck',
        'contact' => 'Contact Founder',
        'share' => 'Share',
        'copy' => 'Copy Link',
        'copied' => 'Copied!',
        'success' => 'Request Received!',
        'success_msg' => 'Thank you. We will contact you soon.',
        'close' => 'Close',
        'gateway' => 'Business Gateway',
        'gateway_sub' => 'Choose your path',
        'investors' => 'Investors Club',
        'inv_desc' => 'Exclusive investment opportunities',
        'enter' => 'Enter Club',
        'partners' => 'Success Partners',
        'part_desc' => 'List your project',
        'register' => 'Register',
        'similar' => 'Similar Projects',
        'similar_sub' => 'More projects in',
        'view_details' => 'View Details',
        'view_all' => 'View All',
    ) : array(
        'back' => 'العودة للمشاريع',
        'featured' => 'مشروع مميز',
        'company' => 'الشركة',
        'views' => 'مشاهدة',
        'details' => 'تفاصيل المشروع',
        'gallery' => 'معرض الصور',
        'pitch' => 'تحميل Pitch Deck',
        'contact' => 'تواصل مع المؤسس',
        'share' => 'مشاركة',
        'copy' => 'نسخ الرابط',
        'copied' => 'تم النسخ!',
        'success' => 'تم استلام طلبك!',
        'success_msg' => 'شكراً لك. سنتواصل معك قريباً.',
        'close' => 'إغلاق',
        'gateway' => 'بوابة الأعمال',
        'gateway_sub' => 'اختر المسار المناسب',
        'investors' => 'نادي المستثمرين',
        'inv_desc' => 'فرص استثمارية حصرية',
        'enter' => 'دخول النادي',
        'partners' => 'شركاء النجاح',
        'part_desc' => 'أدرج مشروعك',
        'register' => 'تسجيل',
        'similar' => 'مشاريع مشابهة',
        'similar_sub' => 'مشاريع أخرى في قطاع',
        'view_details' => 'عرض التفاصيل',
        'view_all' => 'عرض الكل',
    );

    $inv_url = $is_en ? 'https://vcmem.com/en/investors-club/' : 'https://vcmem.com/investors-club/';
    $sp_url  = $is_en ? 'https://pvspaces.com/en/sp-2/' : 'https://pvspaces.com/sp/';
    $dir     = $is_en ? 'ltr' : 'rtl';
    $align   = $is_en ? 'left' : 'right';

    if (!empty($images[0]) && strpos($images[0], 'placeholder') === false) {
        echo '<link rel="preload" as="image" href="' . esc_url($images[0]) . '">' . "\n";
    }
?>

<style>
:root{--c1:<?php echo $is_en?'#1e40af':'#1D2D51';?>;--c1l:<?php echo $is_en?'#3b82f6':'#2a4a7f';?>;--c2:#d4a853;--c2l:#f0d78c;--c2d:#b8923d;--c3:#f8fafc;--c4:#1e293b;--c5:#64748b;--c6:#e2e8f0;--r:14px;--s:0 4px 20px rgba(0,0,0,0.08)}
*{box-sizing:border-box;margin:0;padding:0}
.sp{max-width:900px;margin:0 auto;padding:20px 15px;font-family:<?php echo $is_en?"'Inter',sans-serif":"'Cairo',sans-serif";?>;background:var(--c3);direction:<?php echo $dir;?>;text-align:<?php echo $align;?>;color:var(--c4);line-height:1.7}
.sp-h{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.sp-back{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:transparent;color:var(--c5)!important;border:none;border-radius:6px;text-decoration:none;font-weight:600;font-size:.78rem;transition:all .3s}
.sp-back:hover{color:var(--c1)!important;background:rgba(29,45,81,.06)}
.sp-back svg{width:14px;height:14px;transition:transform .3s}
.sp-back:hover svg{transform:translateX(<?php echo $is_en?'-3':'3';?>px)}
.sp-lang{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#fff;color:var(--c1)!important;border:1.5px solid var(--c6);border-radius:50px;text-decoration:none;font-weight:700;font-size:.8rem;transition:all .3s}
.sp-lang:hover{background:var(--c1);color:#fff!important;border-color:var(--c1)}
.sp-badge{display:inline-flex;align-items:center;gap:10px;background:linear-gradient(135deg,rgba(91,163,217,.15),rgba(160,207,239,.1));border:2px solid #5ba3d9;padding:10px 20px;border-radius:50px;font-weight:800;font-size:.85rem;margin-bottom:18px;color:var(--c1);box-shadow:0 4px 15px rgba(91,163,217,.25)}
.sp-badge-circle{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#e8f4fd 0%,#d0e8fa 30%,#b8dcf8 60%,#a0cfef 100%);display:inline-flex;align-items:center;justify-content:center;border:2px solid #5ba3d9;box-shadow:0 2px 8px rgba(91,163,217,.3);flex-shrink:0}
.sp-badge-circle img{width:24px;height:24px;border-radius:50%;object-fit:contain}
.sp-title{font-size:clamp(1.5rem,4vw,2.2rem);font-weight:900;color:var(--c1);margin-bottom:15px;line-height:1.3}
.sp-tags{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.sp-tag{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:700}
.sp-tag::before{content:'';width:6px;height:6px;border-radius:50%}
.sp-tag-s{background:rgba(30,64,175,.08);color:var(--c1)}.sp-tag-s::before{background:var(--c1)}
.sp-tag-st{background:rgba(212,168,83,.12);color:var(--c2d)}.sp-tag-st::before{background:var(--c2)}
.sp-meta{display:grid;grid-template-columns:repeat(2,1fr);gap:15px;background:#fff;padding:22px;border-radius:var(--r);margin-bottom:25px;box-shadow:var(--s);border:1px solid var(--c6)}
.sp-mi{display:flex;align-items:center;gap:12px;padding:8px 0}
.sp-mi-i{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,rgba(212,168,83,.12),rgba(212,168,83,.05));display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sp-mi-i svg{width:20px;height:20px;color:var(--c2d)}
.sp-mi-c{display:flex;flex-direction:column;gap:2px}
.sp-mi-l{font-size:.75rem;color:var(--c5);font-weight:600}
.sp-mi-v{font-size:.9rem;color:var(--c1);font-weight:700}
.sp-sec{margin-bottom:25px}
.sp-sec-t{font-size:1.15rem;font-weight:800;color:var(--c1);margin-bottom:15px;display:flex;align-items:center;gap:10px}
.sp-sec-t::before{content:'';width:4px;height:22px;background:linear-gradient(180deg,var(--c2),var(--c2l));border-radius:2px}
.sp-gal{position:relative;width:100%;padding-bottom:100%;border-radius:var(--r);overflow:hidden;background:#fff;box-shadow:var(--s);margin-bottom:12px}
.sp-slide{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;transition:opacity .5s}
.sp-slide.active{opacity:1}
.sp-slide img{width:100%;height:100%;object-fit:cover;cursor:zoom-in}
.sp-nav{position:absolute;top:50%;transform:translateY(-50%);width:100%;display:flex;justify-content:space-between;padding:0 10px;pointer-events:none;z-index:10}
.sp-nav-btn{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.9);color:var(--c1);border:none;cursor:pointer;pointer-events:all;transition:all .3s;font-size:16px;font-weight:bold;box-shadow:0 2px 10px rgba(0,0,0,.15)}
.sp-nav-btn:hover{background:var(--c2);color:#fff}
.sp-cnt{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);color:#fff;padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;z-index:5}
.sp-thumbs{display:flex;gap:10px;overflow-x:auto;padding:5px}
.sp-thumbs::-webkit-scrollbar{height:5px}
.sp-thumbs::-webkit-scrollbar-thumb{background:var(--c2);border-radius:3px}
.sp-th{width:70px;height:70px;border-radius:8px;overflow:hidden;cursor:pointer;flex-shrink:0;border:2px solid transparent;transition:all .3s;opacity:.6}
.sp-th:hover{opacity:1}
.sp-th.active{border-color:var(--c2);opacity:1;box-shadow:0 3px 12px rgba(212,168,83,.35)}
.sp-th img{width:100%;height:100%;object-fit:cover}
.sp-det{background:#fff;border-radius:var(--r);padding:25px;margin-bottom:25px;box-shadow:var(--s);border:1px solid var(--c6)}
.sp-det-c{font-size:1rem;line-height:1.9;color:var(--c4)}
.sp-det-c p{margin-bottom:15px}
.sp-det-c p:last-child{margin-bottom:0}
.sp-acts{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:25px}
.sp-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px 28px;border-radius:10px;font-weight:700;font-size:.95rem;text-decoration:none;cursor:pointer;border:none;transition:all .3s;flex:1;min-width:180px;font-family:inherit}
.sp-btn svg{width:20px;height:20px}
.sp-btn-p{background:linear-gradient(135deg,var(--c2),var(--c2l));color:var(--c1)!important}
.sp-btn-p:hover{transform:translateY(-3px);box-shadow:0 10px 30px rgba(212,168,83,.4);color:var(--c1)!important}
.sp-btn-c{background:var(--c1);color:#fff!important}
.sp-btn-c:hover{background:var(--c1l);transform:translateY(-3px);box-shadow:0 10px 30px rgba(29,45,81,.3);color:#fff!important}
.sp-share{background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:var(--r);padding:20px;border:1px solid var(--c6)}
.sp-share-t{font-size:1rem;font-weight:700;color:var(--c1);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.sp-share-t svg{width:18px;height:18px;color:var(--c2)}
.sp-share-b{display:flex;gap:10px;flex-wrap:wrap}
.sp-sh{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;border-radius:8px;font-weight:600;font-size:.82rem;text-decoration:none;cursor:pointer;border:none;transition:all .3s;color:#fff!important}
.sp-sh svg{width:16px;height:16px}
.sp-sh:hover{transform:translateY(-2px)}
.sp-sh-cp{background:#fff;color:var(--c1)!important;border:2px solid var(--c6)}
.sp-sh-cp:hover{background:var(--c1);color:#fff!important;border-color:var(--c1)}
.sp-sh-cp.copied{background:#10b981;color:#fff!important;border-color:#10b981}
.sp-sh-wa{background:#25D366}.sp-sh-wa:hover{background:#1da851}
.sp-sh-tw{background:#000}.sp-sh-tw:hover{background:#333}
.sp-sh-li{background:#0A66C2}.sp-sh-li:hover{background:#084d94}
.sp-modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,.9);backdrop-filter:blur(8px);z-index:999999;display:none;align-items:center;justify-content:center;padding:20px}
.sp-modal.show{display:flex}
.sp-modal-c{background:#fff;border-radius:18px;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;animation:spIn .4s ease;box-shadow:0 20px 60px rgba(0,0,0,.4)}
@keyframes spIn{from{opacity:0;transform:scale(.9) translateY(30px)}to{opacity:1;transform:scale(1) translateY(0)}}
.sp-modal-h{padding:16px 20px;border-bottom:1px solid var(--c6);display:flex;justify-content:space-between;align-items:center;background:#fafafa;border-radius:18px 18px 0 0}
.sp-modal-t{font-size:1rem;font-weight:800;color:var(--c1)}
.sp-modal-x{width:36px;height:36px;border-radius:50%;border:none;background:#fff;cursor:pointer;font-size:1.3rem;display:flex;align-items:center;justify-content:center;transition:all .3s;color:var(--c5);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.sp-modal-x:hover{background:#ef4444;color:#fff!important;transform:rotate(90deg)}
.sp-modal-b{padding:24px;direction:<?php echo $dir;?>;text-align:<?php echo $align;?>}
.sp-suc{display:none;text-align:center;padding:40px 20px}
.sp-suc.show{display:block}
.sp-suc-i{width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;animation:spPop .5s ease}
@keyframes spPop{0%{transform:scale(0)}50%{transform:scale(1.2)}100%{transform:scale(1)}}
.sp-suc-i svg{width:40px;height:40px;color:#fff}
.sp-suc h3{font-size:1.3rem;color:var(--c1);margin-bottom:10px;font-weight:800}
.sp-suc p{color:var(--c5);font-size:.95rem;line-height:1.7;margin-bottom:20px}
.sp-suc-btn{padding:12px 30px;background:var(--c1);color:#fff!important;border-radius:8px;font-weight:700;cursor:pointer;border:none;transition:all .3s;font-family:inherit;font-size:.95rem}
.sp-suc-btn:hover{background:var(--c1l);transform:translateY(-2px);color:#fff!important}
.sp-lb{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.95);z-index:9999999;display:none;align-items:center;justify-content:center;padding:20px}
.sp-lb.show{display:flex}
.sp-lb-img{max-width:90%;max-height:90vh;object-fit:contain;border-radius:8px;animation:spLbIn .3s ease}
@keyframes spLbIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.sp-lb-x{position:absolute;top:20px;right:20px;width:45px;height:45px;border-radius:50%;background:rgba(255,255,255,.1);color:#fff;border:none;cursor:pointer;font-size:22px;display:flex;align-items:center;justify-content:center;transition:all .3s}
.sp-lb-x:hover{background:#ef4444;transform:rotate(90deg)}
.sp-related{margin-top:40px;padding:0}
.sp-related-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;gap:12px;flex-wrap:wrap}
.sp-related-title-wrap{display:flex;flex-direction:column;gap:4px}
.sp-related-title{font-size:1.3rem;font-weight:900;color:var(--c1);display:flex;align-items:center;gap:10px;margin:0;line-height:1.3}
.sp-related-title::before{content:'';width:5px;height:26px;background:linear-gradient(180deg,var(--c2),var(--c2l));border-radius:3px;flex-shrink:0}
.sp-related-subtitle{font-size:.85rem;color:var(--c5);font-weight:600;padding-<?php echo $is_en?'left':'right';?>:15px}
.sp-related-subtitle a{color:var(--c1);font-weight:800;text-decoration:none;border-bottom:2px solid var(--c2);transition:all .3s}
.sp-related-subtitle a:hover{color:var(--c2d)}
.sp-related-controls{display:flex;align-items:center;gap:8px}
.sp-rel-nav{width:40px;height:40px;border-radius:50%;border:2px solid var(--c6);background:#fff;color:var(--c1);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:bold;transition:all .3s;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:0}
.sp-rel-nav:hover{background:var(--c2);color:#fff!important;border-color:var(--c2);transform:scale(1.08)}
.sp-rel-nav:disabled{opacity:.3;cursor:not-allowed;transform:none}
.sp-related-view-all{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:var(--c1);color:#fff!important;border-radius:25px;text-decoration:none;font-size:.8rem;font-weight:700;transition:all .3s}
.sp-related-view-all:hover{background:var(--c2);color:var(--c1)!important;transform:translateY(-2px)}
.sp-related-view-all svg{width:14px;height:14px}
.sp-rel-viewport{overflow:hidden;border-radius:16px}
.sp-rel-track{display:flex;gap:18px;transition:transform .5s cubic-bezier(.4,0,.2,1);will-change:transform}
.sp-rel-card{flex-shrink:0;background:#fff;border-radius:14px;overflow:hidden;box-shadow:var(--s);border:1px solid var(--c6);transition:all .4s;display:flex;flex-direction:column;position:relative}
.sp-rel-card:hover{transform:translateY(-6px);box-shadow:0 15px 40px rgba(0,0,0,.12);border-color:rgba(212,168,83,.3)}
.sp-rel-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--c2),var(--c2l),var(--c2));transform:scaleX(0);transition:transform .4s;z-index:5}
.sp-rel-card:hover::before{transform:scaleX(1)}
.sp-rel-card.is-feat{border:2px solid var(--c2);background:linear-gradient(145deg,#fffef7,#fefcf0)}
.sp-rel-card.is-feat::before{transform:scaleX(1)}
.sp-rel-feat-badge{position:absolute;top:10px;<?php echo $is_en?'left':'right';?>:10px;z-index:10;width:34px;height:34px;background:linear-gradient(135deg,#e8f4fd,#d0e8fa);border:2px solid var(--c2);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(212,168,83,.35)}
.sp-rel-feat-badge img{width:22px;height:22px;border-radius:50%;object-fit:contain}
.sp-rel-img{position:relative;width:100%;padding-bottom:80%;overflow:hidden;background:linear-gradient(135deg,#f1f5f9,#e2e8f0)}
.sp-rel-img img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:transform .5s}
.sp-rel-card:hover .sp-rel-img img{transform:scale(1.06)}
.sp-rel-body{padding:14px;flex:1;display:flex;flex-direction:column}
.sp-rel-card-title{font-size:.92rem;font-weight:800;color:var(--c1);margin-bottom:6px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;transition:color .3s}
.sp-rel-card:hover .sp-rel-card-title{color:var(--c2d)}
.sp-rel-card-excerpt{font-size:.78rem;color:var(--c5);line-height:1.5;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.sp-rel-card-stage{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:rgba(212,168,83,.1);color:var(--c2d);border-radius:5px;font-size:.7rem;font-weight:700;margin-bottom:10px;width:fit-content}
.sp-rel-card-stage::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--c2)}
.sp-rel-card-btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;background:var(--c1);color:#fff!important;border-radius:8px;text-decoration:none;font-size:.82rem;font-weight:700;transition:all .3s;margin-top:auto}
.sp-rel-card-btn:hover{background:var(--c2);color:var(--c1)!important;transform:translateY(-2px)}
.sp-rel-card-btn svg{width:16px;height:16px}
.sp-rel-dots{display:flex;justify-content:center;gap:8px;margin-top:18px}
.sp-rel-dot{width:10px;height:10px;border-radius:50%;background:var(--c6);border:none;cursor:pointer;padding:0;transition:all .3s}
.sp-rel-dot.active{background:var(--c2);transform:scale(1.3);box-shadow:0 2px 8px rgba(212,168,83,.4)}
.sp-rel-dot:hover:not(.active){background:#94a3b8;transform:scale(1.1)}
.sp-gw{margin-top:40px;padding:50px 30px;background:linear-gradient(180deg,#0f172a,#1e293b);border-radius:20px;text-align:center}
.sp-gw-h{margin-bottom:35px}
.sp-gw-h h2{font-size:clamp(22px,4vw,32px);font-weight:900;color:#fff;margin-bottom:8px}
.sp-gw-h p{font-size:.95rem;color:#94a3b8}
.sp-gw-g{display:grid;grid-template-columns:repeat(2,1fr);gap:25px;max-width:800px;margin:0 auto}
.sp-gw-c{background:rgba(30,41,59,.8);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:30px 25px;text-align:center;transition:all .3s}
.sp-gw-c:hover{transform:translateY(-8px);border-color:rgba(255,255,255,.2)}
.sp-gw-c.inv:hover{box-shadow:0 20px 50px rgba(212,168,83,.25)}
.sp-gw-c.par:hover{box-shadow:0 20px 50px rgba(59,130,246,.25)}
.sp-gw-l{width:auto;height:120px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center}
.sp-gw-l img{max-width:100%;max-height:100%;object-fit:contain;transition:transform .3s}
.sp-gw-c:hover .sp-gw-l img{transform:scale(1.08)}
.sp-gw-c h3{font-size:1.2rem;font-weight:800;margin-bottom:10px}
.sp-gw-c.inv h3{color:#fef3c7}
.sp-gw-c.par h3{color:#dbeafe}
.sp-gw-c p{font-size:.88rem;line-height:1.7;color:#94a3b8;margin-bottom:20px;min-height:50px}
.sp-gw-btn{display:inline-block;padding:12px 32px;border-radius:50px;text-decoration:none;font-weight:700;font-size:.9rem;transition:all .3s}
.sp-gw-btn-g{background:linear-gradient(135deg,#d4a853,#b8923d);color:#0f172a!important}
.sp-gw-btn-g:hover{transform:scale(1.05);box-shadow:0 10px 30px rgba(212,168,83,.4);color:#0f172a!important}
.sp-gw-btn-b{background:linear-gradient(135deg,#60a5fa,#3b82f6);color:#fff!important}
.sp-gw-btn-b:hover{transform:scale(1.05);box-shadow:0 10px 30px rgba(59,130,246,.4);color:#fff!important}
@media(max-width:768px){
    .sp{padding:15px 12px}
    .sp-h{flex-direction:column;align-items:stretch}
    .sp-back,.sp-lang{justify-content:center;text-align:center}
    .sp-title{font-size:1.4rem}
    .sp-meta{grid-template-columns:1fr;padding:18px}
    .sp-gal{padding-bottom:100%}
    .sp-nav-btn{width:36px;height:36px;font-size:14px}
    .sp-th{width:60px;height:60px}
    .sp-det{padding:20px 15px}
    .sp-acts{flex-direction:column}
    .sp-btn{min-width:unset;width:100%}
    .sp-share-b{flex-direction:column}
    .sp-sh{justify-content:center}
    .sp-modal-c{max-width:95%;border-radius:14px}
    .sp-modal-h{padding:14px 16px}
    .sp-modal-b{padding:18px 15px}
    .sp-badge-circle{width:32px;height:32px}
    .sp-badge-circle img{width:20px;height:20px}
    .sp-badge{font-size:.8rem;padding:8px 16px}
    .sp-related-header{flex-direction:column;align-items:stretch;gap:12px;margin-bottom:16px}
    .sp-related-controls{justify-content:center}
    .sp-related-title{font-size:1.1rem}
    .sp-rel-nav{width:36px;height:36px;font-size:13px}
    .sp-gw{padding:35px 20px}
    .sp-gw-g{grid-template-columns:1fr;gap:20px}
    .sp-gw-c{padding:25px 20px}
    .sp-gw-c p{min-height:auto}
}
@media(max-width:480px){
    .sp-tag{padding:6px 12px;font-size:.78rem}
    .sp-mi-i{width:38px;height:38px}
    .sp-mi-i svg{width:18px;height:18px}
    .sp-gal{padding-bottom:90%}
}
</style>

<div class="sp">
    <div class="sp-h">
        <a href="<?php echo esc_url($back_url);?>" class="sp-back">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M<?php echo $is_en?'15 19l-7-7 7-7':'9 5l7 7-7 7';?>"/></svg>
            <?php echo esc_html($t['back']);?>
        </a>
        <a href="<?php echo esc_url($other_url);?>" class="sp-lang"><?php echo $is_en?'🇸🇦 العربية':'🇬🇧 English';?></a>
    </div>

    <?php if($is_featured==='1'):?>
    <div class="sp-badge">
        <span class="sp-badge-circle"><img src="<?php echo esc_url($logo_badge);?>" alt="Vibes"></span>
        <span><?php echo esc_html($t['featured']);?></span>
    </div>
    <?php endif;?>

    <h1 class="sp-title"><?php echo esc_html($title);?></h1>

    <div class="sp-tags">
        <?php if($sector):?><span class="sp-tag sp-tag-s"><?php echo esc_html($sector);?></span><?php endif;?>
        <?php if($stage):?><span class="sp-tag sp-tag-st"><?php echo esc_html($stage);?></span><?php endif;?>
    </div>

    <!-- Meta Grid: Company + Views Only -->
    <div class="sp-meta">
        <?php if($comp):?>
        <div class="sp-mi">
            <div class="sp-mi-i"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
            <div class="sp-mi-c"><span class="sp-mi-l"><?php echo esc_html($t['company']);?></span><span class="sp-mi-v"><?php echo esc_html($comp);?></span></div>
        </div>
        <?php endif;?>
        <div class="sp-mi">
            <div class="sp-mi-i"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></div>
            <div class="sp-mi-c"><span class="sp-mi-l"><?php echo esc_html($t['views']);?></span><span class="sp-mi-v"><?php echo number_format($views);?></span></div>
        </div>
    </div>

    <?php if(!empty($images)):?>
    <div class="sp-sec">
        <h2 class="sp-sec-t"><?php echo esc_html($t['gallery']);?></h2>
        <div class="sp-gal" id="spGal">
            <?php foreach($images as $i=>$img):?>
            <div class="sp-slide <?php echo $i===0?'active':'';?>" data-i="<?php echo $i;?>">
                <img src="<?php echo esc_url($img);?>"
                     alt="<?php echo esc_attr($title);?>"
                     <?php echo $i===0 ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"'; ?>
                     data-full="<?php echo esc_url($full_images[$i]); ?>"
                     onclick="spLb(<?php echo $i;?>)">
            </div>
            <?php endforeach;?>
            <?php if(count($images)>1):?>
            <div class="sp-nav">
                <button type="button" class="sp-nav-btn" onclick="spPrev()">❮</button>
                <button type="button" class="sp-nav-btn" onclick="spNext()">❯</button>
            </div>
            <div class="sp-cnt"><span id="spIdx">1</span>/<?php echo count($images);?></div>
            <?php endif;?>
        </div>
        <?php if(count($images)>1):?>
        <div class="sp-thumbs">
            <?php foreach($thumb_images as $i=>$thimg):?>
            <div class="sp-th <?php echo $i===0?'active':'';?>" onclick="spGo(<?php echo $i;?>)" data-i="<?php echo $i;?>">
                <img src="<?php echo esc_url($thimg);?>" alt="" loading="lazy">
            </div>
            <?php endforeach;?>
        </div>
        <?php endif;?>
    </div>
    <?php endif;?>

    <?php if($desc):?>
    <div class="sp-det">
        <h2 class="sp-sec-t"><?php echo esc_html($t['details']);?></h2>
        <div class="sp-det-c"><?php echo wpautop(esc_html($desc));?></div>
    </div>
    <?php endif;?>

    <div class="sp-acts">
        <?php if($pitch_url):?>
        <button type="button" class="sp-btn sp-btn-p" onclick="spPitchOpen()">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <?php echo esc_html($t['pitch']);?>
        </button>
        <?php endif;?>
        <button type="button" class="sp-btn sp-btn-c" onclick="spContactOpen()">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <?php echo esc_html($t['contact']);?>
        </button>
    </div>

    <div class="sp-share">
        <h3 class="sp-share-t">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            <?php echo esc_html($t['share']);?>
        </h3>
        <div class="sp-share-b">
            <button type="button" class="sp-sh sp-sh-cp" id="spCp" onclick="spCopy()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                <span id="spCpTxt"><?php echo esc_html($t['copy']);?></span>
            </button>
            <a href="https://wa.me/?text=<?php echo urlencode($title.' - '.$current_url);?>" target="_blank" class="sp-sh sp-sh-wa">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                WhatsApp
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($current_url);?>&text=<?php echo urlencode($title);?>" target="_blank" class="sp-sh sp-sh-tw">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($current_url);?>" target="_blank" class="sp-sh sp-sh-li">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
        </div>
    </div>

    <!-- Similar Projects Slider -->
    <?php
    $slider_sectors = get_the_terms($pid, 'sector');
    if ($slider_sectors && !is_wp_error($slider_sectors) && !empty($slider_sectors[0])):
        $slider_sector      = $slider_sectors[0];
        $slider_sector_name = function_exists('pdp_translate_term') ? pdp_translate_term($slider_sector->name, 'sector', $lang) : $slider_sector->name;
        $slider_sector_url  = $back_url . '?sector=' . $slider_sector->term_id;

        $slider_q = new WP_Query(array(
            'post_type'      => 'project',
            'posts_per_page' => 12,
            'post__not_in'   => array($pid),
            'post_status'    => 'publish',
            'orderby'        => 'rand',
            'tax_query'      => array(array(
                'taxonomy' => 'sector',
                'field'    => 'term_id',
                'terms'    => $slider_sector->term_id,
            )),
        ));

        if ($slider_q->have_posts()):
            $slider_cards = array();
            while ($slider_q->have_posts()) {
                $slider_q->the_post();
                $rid    = get_the_ID();
                $rtitle = ($is_en && get_post_meta($rid,'title_en',true)) ? get_post_meta($rid,'title_en',true) : get_the_title($rid);
                $rimg   = '';
                if (has_post_thumbnail($rid)) {
                    $rimg = get_the_post_thumbnail_url($rid, 'medium');
                } else {
                    $gal = get_post_meta($rid, 'project_gallery', true);
                    if ($gal) {
                        $gids = array_filter(explode(',', $gal));
                        if (!empty($gids[0])) $rimg = wp_get_attachment_image_url((int)$gids[0], 'medium');
                    }
                }
                if (!$rimg) $rimg = 'https://via.placeholder.com/400x400/1D2D51/C9A961?text=' . urlencode($rtitle);
                $rlink    = get_permalink($rid) . ($is_en ? '?lang=en' : '');
                $rdetails = $is_en ? (get_post_meta($rid,'project_details_en',true) ?: get_post_meta($rid,'project_details',true)) : get_post_meta($rid,'project_details',true);
                $rexcerpt = $rdetails ? wp_trim_words(strip_tags($rdetails), 10, '...') : '';
                $rstage   = '';
                $rst      = get_the_terms($rid, 'project_stage');
                if ($rst && !is_wp_error($rst) && !empty($rst[0])) {
                    $rstage = function_exists('pdp_translate_term') ? pdp_translate_term($rst[0]->name, 'stage', $lang) : $rst[0]->name;
                }
                $rfeat = (get_post_meta($rid, 'is_featured', true) === '1');
                $slider_cards[] = compact('rtitle','rimg','rlink','rexcerpt','rstage','rfeat');
            }
            wp_reset_postdata();
    ?>
    <div class="sp-related" id="spRelatedSection">
        <div class="sp-related-header">
            <div class="sp-related-title-wrap">
                <h2 class="sp-related-title"><?php echo esc_html($t['similar']);?></h2>
                <span class="sp-related-subtitle"><?php echo esc_html($t['similar_sub']);?> <a href="<?php echo esc_url($slider_sector_url);?>"><?php echo esc_html($slider_sector_name);?></a></span>
            </div>
            <div class="sp-related-controls">
                <button type="button" class="sp-rel-nav" id="spRelPrev"><?php echo $is_en ? '&#10094;' : '&#10095;';?></button>
                <button type="button" class="sp-rel-nav" id="spRelNext"><?php echo $is_en ? '&#10095;' : '&#10094;';?></button>
                <a href="<?php echo esc_url($slider_sector_url);?>" class="sp-related-view-all"><?php echo esc_html($t['view_all']);?><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M<?php echo $is_en?'13 7l5 5m0 0l-5 5m5-5H6':'11 17l-5-5m0 0l5-5m-5 5h12';?>"/></svg></a>
            </div>
        </div>
        <div class="sp-rel-viewport">
            <div class="sp-rel-track" id="spRelTrack">
                <?php foreach ($slider_cards as $c): ?>
                <div class="sp-rel-card<?php echo $c['rfeat']?' is-feat':''; ?>">
                    <?php if ($c['rfeat']): ?>
                    <div class="sp-rel-feat-badge"><img src="<?php echo esc_url($logo_badge);?>" alt="Featured"></div>
                    <?php endif; ?>
                    <div class="sp-rel-img"><img src="<?php echo esc_url($c['rimg']);?>" alt="<?php echo esc_attr($c['rtitle']);?>" loading="lazy"></div>
                    <div class="sp-rel-body">
                        <h3 class="sp-rel-card-title"><?php echo esc_html($c['rtitle']);?></h3>
                        <?php if ($c['rexcerpt']): ?><p class="sp-rel-card-excerpt"><?php echo esc_html($c['rexcerpt']);?></p><?php endif; ?>
                        <?php if ($c['rstage']): ?><span class="sp-rel-card-stage"><?php echo esc_html($c['rstage']);?></span><?php endif; ?>
                        <a href="<?php echo esc_url($c['rlink']);?>" class="sp-rel-card-btn"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><?php echo esc_html($t['view_details']);?></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="sp-rel-dots" id="spRelDots"></div>
    </div>
    <?php
        endif;
    endif;
    ?>

    <!-- Gateway -->
    <div class="sp-gw">
        <div class="sp-gw-h">
            <h2><?php echo esc_html($t['gateway']);?></h2>
            <p><?php echo esc_html($t['gateway_sub']);?></p>
        </div>
        <div class="sp-gw-g">
            <div class="sp-gw-c inv">
                <a href="<?php echo esc_url($inv_url);?>" target="_blank" class="sp-gw-l"><img src="<?php echo esc_url($logo_investors);?>" alt="<?php echo esc_attr($t['investors']);?>" loading="lazy"></a>
                <h3><?php echo esc_html($t['investors']);?></h3>
                <p><?php echo esc_html($t['inv_desc']);?></p>
                <a class="sp-gw-btn sp-gw-btn-g" href="<?php echo esc_url($inv_url);?>" target="_blank"><?php echo esc_html($t['enter']);?></a>
            </div>
            <div class="sp-gw-c par">
                <a href="<?php echo esc_url($sp_url);?>" target="_blank" class="sp-gw-l"><img src="<?php echo esc_url($logo_sp);?>" alt="<?php echo esc_attr($t['partners']);?>" loading="lazy"></a>
                <h3><?php echo esc_html($t['partners']);?></h3>
                <p><?php echo esc_html($t['part_desc']);?></p>
                <a class="sp-gw-btn sp-gw-btn-b" href="<?php echo esc_url($sp_url);?>" target="_blank"><?php echo esc_html($t['register']);?></a>
            </div>
        </div>
    </div>

</div><!-- .sp -->

<!-- Pitch Deck Modal -->
<div class="sp-modal" id="spPitchM">
    <div class="sp-modal-c">
        <div class="sp-modal-h">
            <h3 class="sp-modal-t"><?php echo esc_html($t['pitch']);?></h3>
            <button type="button" class="sp-modal-x" onclick="spPitchClose()">&times;</button>
        </div>
        <div class="sp-modal-b">
            <div id="spPitchF">
                <?php echo do_shortcode($is_en ? '[fluentform id="32"]' : '[fluentform id="31"]'); ?>
            </div>
            <div class="sp-suc" id="spPitchS">
                <div class="sp-suc-i"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>
                <h3><?php echo esc_html($t['success']);?></h3>
                <p><?php echo esc_html($t['success_msg']);?></p>
                <button type="button" class="sp-suc-btn" onclick="spPitchClose()"><?php echo esc_html($t['close']);?></button>
            </div>
        </div>
    </div>
</div>

<!-- Contact Founder Modal -->
<div class="sp-modal" id="spContactM">
    <div class="sp-modal-c">
        <div class="sp-modal-h">
            <h3 class="sp-modal-t"><?php echo esc_html($t['contact']);?></h3>
            <button type="button" class="sp-modal-x" onclick="spContactClose()">&times;</button>
        </div>
        <div class="sp-modal-b">
            <?php echo do_shortcode($is_en ? '[fluentform id="33"]' : '[fluentform id="30"]'); ?>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div class="sp-lb" id="spLbx" onclick="spLbClose()">
    <button type="button" class="sp-lb-x">&times;</button>
    <img src="" alt="" class="sp-lb-img" id="spLbImg" onclick="event.stopPropagation()">
</div>

<!-- JavaScript -->
<script>
(function(){
    'use strict';

    var imgs=<?php echo wp_json_encode($images);?>,
        fullImgs=<?php echo wp_json_encode($full_images);?>,
        idx=0,
        isRTL=<?php echo $is_en?'false':'true';?>,
        data={
            project_name:<?php echo wp_json_encode($title);?>,
            founder_name:<?php echo wp_json_encode($found);?>,
            founder_whatsapp:<?php echo wp_json_encode($whatsapp);?>,
            pitch_url:<?php echo wp_json_encode($pitch_url);?>,
            url:<?php echo wp_json_encode($current_url);?>,
            copied:<?php echo wp_json_encode($t['copied']);?>,
            copy:<?php echo wp_json_encode($t['copy']);?>
        };

    window.spGo=function(i){idx=i;updGal();};
    window.spNext=function(){idx=(idx+1)%imgs.length;updGal();};
    window.spPrev=function(){idx=(idx-1+imgs.length)%imgs.length;updGal();};
    function updGal(){
        document.querySelectorAll('.sp-slide').forEach(function(s,i){s.classList.toggle('active',i===idx);});
        document.querySelectorAll('.sp-th').forEach(function(t,i){t.classList.toggle('active',i===idx);});
        var el=document.getElementById('spIdx');if(el)el.textContent=idx+1;
    }
    var galEl=document.getElementById('spGal'),autoGal;
    if(galEl&&imgs.length>1){
        galEl.onmouseenter=function(){clearInterval(autoGal);};
        galEl.onmouseleave=function(){autoGal=setInterval(spNext,4000);};
        autoGal=setInterval(spNext,4000);
    }

    window.spLb=function(i){
        if(typeof i!=='undefined')idx=i;
        var lb=document.getElementById('spLbx'),lbImg=document.getElementById('spLbImg');
        if(!lb||!lbImg)return;
        lbImg.src=fullImgs[idx]||imgs[idx];
        lb.classList.add('show');document.body.style.overflow='hidden';
    };
    window.spLbClose=function(){
        var lb=document.getElementById('spLbx');
        if(lb){lb.classList.remove('show');document.body.style.overflow='';}
    };

    window.spCopy=function(){
        var btn=document.getElementById('spCp'),txt=document.getElementById('spCpTxt');
        if(navigator.clipboard&&navigator.clipboard.writeText){
            navigator.clipboard.writeText(data.url).then(function(){
                if(btn)btn.classList.add('copied');if(txt)txt.textContent=data.copied;
                setTimeout(function(){if(btn)btn.classList.remove('copied');if(txt)txt.textContent=data.copy;},2000);
            });
        }
    };

    function fillFields(){
        var fieldMap=[
            {names:['project_name','project-name','names_1','hidden_field_1'],val:data.project_name},
            {names:['pitch_url','pitch-url','names_2','hidden_field_2'],val:data.pitch_url},
            {names:['founder_name','founder-name','names_3','hidden_field_3'],val:data.founder_name},
            {names:['founder_whatsapp','whatsapp','names_4','hidden_field_4'],val:data.founder_whatsapp}
        ];
        fieldMap.forEach(function(f){
            f.names.forEach(function(n){
                document.querySelectorAll('input[name="'+n+'"]').forEach(function(input){
                    if(f.val)input.value=f.val;
                });
            });
        });
    }

    window.spPitchOpen=function(){
        var f=document.getElementById('spPitchF'),s=document.getElementById('spPitchS');
        if(f)f.style.display='block';if(s)s.classList.remove('show');
        var m=document.getElementById('spPitchM');
        if(m){m.classList.add('show');document.body.style.overflow='hidden';}
        setTimeout(fillFields,150);
    };
    window.spPitchClose=function(){
        var m=document.getElementById('spPitchM');
        if(m){m.classList.remove('show');document.body.style.overflow='';}
        setTimeout(function(){
            var f=document.getElementById('spPitchF'),s=document.getElementById('spPitchS');
            if(f)f.style.display='block';if(s)s.classList.remove('show');
        },300);
    };

    window.spContactOpen=function(){
        var m=document.getElementById('spContactM');
        if(m){m.classList.add('show');document.body.style.overflow='hidden';}
        setTimeout(fillFields,150);
    };
    window.spContactClose=function(){
        var m=document.getElementById('spContactM');
        if(m){m.classList.remove('show');document.body.style.overflow='';}
    };

    document.querySelectorAll('.sp-modal').forEach(function(m){
        m.addEventListener('click',function(e){
            if(e.target===this){this.classList.remove('show');document.body.style.overflow='';}
        });
    });

    document.addEventListener('keydown',function(e){
        if(e.key==='Escape'){spLbClose();spPitchClose();spContactClose();}
        if(imgs.length>1){
            if(e.key==='ArrowRight')spNext();
            if(e.key==='ArrowLeft')spPrev();
        }
    });

    if(typeof jQuery!=='undefined'){
        jQuery(document).on('fluentform_submission_success',function(e,r,form){
            if(jQuery(form).closest('#spPitchM').length>0){
                var f=document.getElementById('spPitchF'),s=document.getElementById('spPitchS');
                if(f)f.style.display='none';if(s)s.classList.add('show');
            }
        });
    }

    document.addEventListener('DOMContentLoaded',fillFields);
    setTimeout(fillFields,500);

    /* Related Projects Slider */
    var relTrack=document.getElementById('spRelTrack');
    var relPrev=document.getElementById('spRelPrev');
    var relNext=document.getElementById('spRelNext');
    var relDots=document.getElementById('spRelDots');
    var relSection=document.getElementById('spRelatedSection');

    if(relTrack&&relPrev&&relNext){
        var relCards=Array.prototype.slice.call(relTrack.querySelectorAll('.sp-rel-card'));
        var relTotal=relCards.length;
        var relIdx=0;
        var relTimer;

        function relPerView(){
            var w=window.innerWidth;
            if(w<=500)return 1;
            if(w<=768)return 1.3;
            if(w<=1024)return 2;
            return 3;
        }
        function relMaxIdx(){return Math.max(0,Math.ceil(relTotal-relPerView()));}

        function relRender(){
            var pv=relPerView();
            var gap=18;
            var vw=relTrack.parentElement.offsetWidth;
            var cardW=(vw-gap*(Math.floor(pv)-1))/pv;
            relCards.forEach(function(c){c.style.minWidth=cardW+'px';c.style.maxWidth=cardW+'px';});
            var offset=relIdx*(cardW+gap);
            relTrack.style.transform='translateX('+(isRTL?offset:-offset)+'px)';
            relBuildDots();
            relUpdBtns();
        }

        function relBuildDots(){
            if(!relDots)return;
            var mx=relMaxIdx();
            var html='';
            for(var i=0;i<=mx;i++){
                html+='<button type="button" class="sp-rel-dot'+(i===relIdx?' active':'')+'" data-i="'+i+'"></button>';
            }
            relDots.innerHTML=html;
            relDots.querySelectorAll('.sp-rel-dot').forEach(function(d){
                d.addEventListener('click',function(){relGoTo(parseInt(this.dataset.i,10));relResetAuto();});
            });
        }

        function relUpdBtns(){
            relPrev.disabled=(relIdx<=0);
            relNext.disabled=(relIdx>=relMaxIdx());
        }

        function relGoTo(i){
            var mx=relMaxIdx();
            if(i<0)i=0;if(i>mx)i=mx;
            relIdx=i;relRender();
        }
        function relGoNext(){relIdx>=relMaxIdx()?relGoTo(0):relGoTo(relIdx+1);}
        function relGoPrev(){relIdx<=0?relGoTo(relMaxIdx()):relGoTo(relIdx-1);}

        relPrev.addEventListener('click',function(){relGoPrev();relResetAuto();});
        relNext.addEventListener('click',function(){relGoNext();relResetAuto();});

        var relSx=0;
        relTrack.addEventListener('touchstart',function(e){relSx=e.changedTouches[0].screenX;},{passive:true});
        relTrack.addEventListener('touchend',function(e){
            var diff=relSx-e.changedTouches[0].screenX;
            if(isRTL)diff=-diff;
            if(Math.abs(diff)>40){diff>0?relGoNext():relGoPrev();relResetAuto();}
        },{passive:true});

        function relStartAuto(){relTimer=setInterval(relGoNext,4000);}
        function relResetAuto(){clearInterval(relTimer);relStartAuto();}

        if(relSection){
            relSection.addEventListener('mouseenter',function(){clearInterval(relTimer);});
            relSection.addEventListener('mouseleave',function(){relStartAuto();});
        }

        var relResizeTimer;
        window.addEventListener('resize',function(){
            clearTimeout(relResizeTimer);
            relResizeTimer=setTimeout(function(){relGoTo(Math.min(relIdx,relMaxIdx()));},200);
        });

        relRender();
        relStartAuto();
    }

})();
</script>

<?php
// ─── رقم المشروع في صفحة المشروع الواحد ───
$project_number = get_post_meta($pid, 'project_number', true);
if ($project_number) {
    $count_obj = wp_count_posts('project');
    $total_projects = ($count_obj && isset($count_obj->publish)) ? (int) $count_obj->publish : 0;
    $digits = strlen((string) $total_projects);
    if ($digits < 2) $digits = 2;
    $formatted_num = str_pad((int) $project_number, $digits, '0', STR_PAD_LEFT);
?>
<style>
.pds-pn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,rgba(29,45,81,.06),rgba(29,45,81,.02));border:2px solid rgba(29,45,81,.12);padding:8px 18px;border-radius:10px;margin-bottom:14px;transition:all .3s}
.pds-pn:hover{border-color:rgba(212,168,83,.4);background:linear-gradient(135deg,rgba(212,168,83,.08),rgba(212,168,83,.03))}
.pds-pn-l{font-size:.85rem;font-weight:600;color:#64748b}
.pds-pn-v{background:linear-gradient(135deg,#1D2D51,#2a4a7f);color:#d4a853;padding:5px 16px;border-radius:8px;font-weight:900;font-size:1.05rem;font-family:'Courier New',monospace;letter-spacing:2px;box-shadow:0 2px 8px rgba(29,45,81,.2)}
</style>
<script>
(function(){
    function addProjectNumber(){
        if(document.querySelector('.pds-pn'))return;
        var title=document.querySelector('.sp-title');
        if(!title)return;
        var el=document.createElement('div');
        el.className='pds-pn';
        el.innerHTML='<span class="pds-pn-l"><?php echo $is_en ? 'Project No.' : 'رقم المشروع'; ?></span><span class="pds-pn-v">#<?php echo esc_js($formatted_num); ?></span>';
        title.parentNode.insertBefore(el,title.nextSibling);
    }
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',addProjectNumber);}
    else{addProjectNumber();}
})();
</script>
<?php } ?>

<?php endwhile; get_footer(); ?>
