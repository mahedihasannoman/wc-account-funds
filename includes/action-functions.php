<?php
defined( 'ABSPATH' ) || exit();

/**
 * Provide account funds credits when a new user registers in the the website
 * @param $user_id
 * @since 1.0.0
 */
function wc_accounts_funds_user_registration_fund($user_id){
	$enable_credit = get_option('wc_af_apply_credit_registration');
	if($enable_credit == 'yes'){
		$credit_amount = get_option('wc_af_registration_credit_amount');
		$details = __('Balance credited for new user registration', 'wc-accounts-funds');
		wc_accounts_funds_add_funds( $user_id, $credit_amount, $details );
		$email_subject = get_option('wc_af_registration_credit_email_subject');
		$message = get_option('wc_af_registration_credit_desc');
		wc_accounts_funds_sent_credit_email($email_subject,$message,$user_id);
		
	}
}
add_action('user_register','wc_accounts_funds_user_registration_fund',10,1);

/**
 * Make topup product is purchase able
 * 
 * @since 1.0.0
 * 
 * @param bool $is_purchasable
 * 
 * @param object $product
 * 
 * @return bool
 */
add_filter('woocommerce_is_purchasable', 'bt_allow_topup_product_purchase_able', 10, 2 );
 
function bt_allow_topup_product_purchase_able( $is_purchasable, $product ) {
	$topup_product_id = get_option( 'wc_accounts_funds_topup_product' );
	if( $product->get_id() == $topup_product_id ) {
		return true;
	}
 
	return $is_purchasable;
}

/**
 * Provide account funds credits when a user posts comments in woocommerce product
 * @param $comment_id
 * @param $comment_approved
 * @param $commentdata
 * @since 1.0.0
 * @return null
 */
function wc_accounts_funds_new_comment_fund($comment_id,$comment_approved,$commentdata){
	$enable_credit = get_option('wc_af_apply_product_review');
	if($enable_credit == 'yes'){
		$commented_post_type = get_post_type($commentdata['comment_post_ID']);
		$product = wc_get_product($commentdata['comment_post_ID']);
		$user_id = $commentdata['user_id'];
		if('product' == $commented_post_type && $product && ! wc_accounts_funds_is_user_reviewed( $user_id, $commentdata['comment_post_ID'] )){
			if($commentdata['comment_approved'] == 1){
				$credit_amount = get_option('wc_af_review_credit_amount');
				$details = __('Balance credited for product review', 'wc-accounts-funds');
				wc_accounts_funds_add_funds($user_id,$credit_amount, $details);
				$email_subject = get_option('wc_af_review_credit_email_subject');
				$message = get_option('wc_af_review_credit_desc');
				wc_accounts_funds_sent_credit_email($email_subject,$message,$user_id);
				wc_accounts_funds_add_user_review( $user_id, $commentdata['comment_post_ID'] );
			}
		}
	}
}
add_action('comment_post','wc_accounts_funds_new_comment_fund',10,3);

/**
 * Provide account funds credits when a review approves manually
 * @param $new_status
 * @param $old_status
 * @param $comment
 * @since 1.0.0
 *
 */
function wc_accounts_funds_comment_approved_funds($new_status,$old_status,$comment){
	$enable_credit = get_option('wc_af_apply_product_review');
	if($enable_credit == 'yes'){
		$commented_post_type = get_post_type($comment->comment_post_ID);
		$user_id = $comment->user_id;
		if('product' == $commented_post_type && ! wc_accounts_funds_is_user_reviewed( $user_id, $comment->comment_post_ID ) ){
			$product = wc_get_product($comment->comment_post_ID);
			if($new_status == 'approved' && $product){
				$credit_amount = get_option('wc_af_review_credit_amount');
				$details = __('Balance credited for product review', 'wc-accounts-funds');
				wc_accounts_funds_add_funds( $user_id, $credit_amount, $details);
				$email_subject = get_option('wc_af_review_credit_email_subject');
				$message = get_option('wc_af_review_credit_desc');
				wc_accounts_funds_sent_credit_email($email_subject,$message,$user_id);
				wc_accounts_funds_add_user_review( $user_id, $comment->comment_post_ID );
				
			}
		}
	}
}
add_action('transition_comment_status','wc_accounts_funds_comment_approved_funds',10,3);

/**
 * Provide account funds credits when a user revisits the website
 * Minimum duration 1 day
 * @since 1.0.0
 */
function wc_accounts_funds_site_visit_funds(){
	if(!is_user_logged_in()){
		return;
	}
	$enable_credit = get_option('wc_af_apply_daily_visits');
	$credit_amount = get_option('wc_af_daily_visits_credit_amount');
	$exclude_user_roles = get_option('wc_af_daily_visits_exclude_users');
	$interval = get_option('wc_af_daily_visits_interval');
	
	if($enable_credit != 'yes'){
		return;
	}
	$current_user_id = get_current_user_id();
	$current_user = new WP_User($current_user_id);
	$current_user_roles = $current_user->roles;
	//check if the user roles are not matched with excluded user roles
	if(isset($exclude_user_roles) && !array_diff($current_user_roles,$exclude_user_roles)){
		return;
	}
	if(get_transient('wc_accounts_funds_site_revisits_'.$current_user_id)){
		return;
	}
	if(!headers_sent() && did_action('wp_loaded')){
		set_transient('wc_accounts_funds_site_revisits_'.$current_user_id,true,DAY_IN_SECONDS * $interval);
	}
	$details = __('Balance credited for daily visit', 'wc-accounts-funds');
	wc_accounts_funds_add_funds( $current_user_id, $credit_amount, $details );
	$email_subject = get_option('wc_af_daily_visits_credit_email_subject');
	$message = get_option('wc_af_daily_visits_credit_desc');
	wc_accounts_funds_sent_credit_email($email_subject,$message,$current_user_id);
}

add_action('wp','wc_accounts_funds_site_visit_funds',100);

