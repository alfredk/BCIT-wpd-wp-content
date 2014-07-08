<?php
/**
 * class-groups-ws-user.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-woocommerce
 * @since groups-woocommerce 1.3.0
 */

/**
 * Adds user membership info on profile pages.
 */
class Groups_WS_User {

	/**
	 * Adds action hooks.
	 */
	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_profile' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'edit_user_profile' ) );
	}

	/**
	 * Own profile.
	 * @param WP_User $user
	 */
	public static function show_user_profile( $user ) {
		$options = get_option( 'groups-woocommerce', null );
		$show_in_user_profile = isset( $options[GROUPS_WS_SHOW_IN_USER_PROFILE] ) ? $options[GROUPS_WS_SHOW_IN_USER_PROFILE] : GROUPS_WS_DEFAULT_SHOW_IN_USER_PROFILE;
		if ( $show_in_user_profile ) {
			self::show_buckets( $user );
			if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
				self::show_subscriptions( $user );
			}
		}
	}

	/**
	 * A user's profile.
	 * @param WP_User $user
	 */
	public static function edit_user_profile( $user ) {
		$options = get_option( 'groups-woocommerce', null );
		$show_in_edit_profile = isset( $options[GROUPS_WS_SHOW_IN_EDIT_PROFILE] ) ? $options[GROUPS_WS_SHOW_IN_EDIT_PROFILE] : GROUPS_WS_DEFAULT_SHOW_IN_EDIT_PROFILE;
		if ( $show_in_edit_profile ) {
			self::show_buckets( $user );
			if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
				self::show_subscriptions( $user );
			}
		}
	}

	/**
	 * Renders group subscription info for the user.
	 * 
	 * @param object $user
	 */
	private static function show_subscriptions( $user ) {

		echo '<h3>';
		echo __( 'Group Subscriptions', GROUPS_WS_PLUGIN_DOMAIN );
		echo '</h3>';

		require_once( GROUPS_WS_VIEWS_LIB . '/class-groups-ws-subscriptions-table-renderer.php' );
			$table = Groups_WS_Subscriptions_Table_Renderer::render( array(
				'status' => 'active,cancelled',
				'exclude_cancelled_after_end_of_prepaid_term' => true,
				'user_id' => $user->ID,
				'columns' => array( 'groups', 'start_date', 'expiry_date', 'end_date' )
			),
			$n
		);
		echo apply_filters( 'groups_woocommerce_show_subscriptions_style', '<style type="text/css">div.subscriptions-count { padding: 0px 0px 1em 2px; } div.group-subscriptions table th { text-align:left; padding-right: 1em; }</style>' );
		echo '<div class="subscriptions-count">';
		if ( $n > 0 ) {
			echo sprintf( _n( 'One subscription.', '%d subscriptions.', $n, GROUPS_WS_PLUGIN_DOMAIN ), $n );
		} else {
			echo __( 'No subscriptions.', GROUPS_WS_PLUGIN_DOMAIN );
		}
		echo '</div>';
		echo '<div class="group-subscriptions">';
		echo $table;
		echo '</div>';
	} 

	/**
	 * Renders time-limited group membership info for the user.
	 * 
	 * The <code>groups_woocommerce_show_buckets_membership</code> filter can be used to modify how membership info is rendered.
	 * 
	 * @param object $user
	 */
	private static function show_buckets( $user ) {

		$user_buckets = get_user_meta( $user->ID, '_groups_buckets', true );
		if ( $user_buckets ) {
			echo '<h3>';
			echo __( 'Group Memberships', GROUPS_WS_PLUGIN_DOMAIN );
			echo '</h3>';
			echo '<ul>';
			uksort( $user_buckets, array( __CLASS__, 'bucket_cmp' ) );
			foreach( $user_buckets as $group_id => $timestamps ) {
				if ( $group = Groups_Group::read( $group_id ) ) {
					if ( Groups_User_Group::read( $user->ID, $group_id ) ) {
						echo '<li>';
						$ts = null;
						foreach( $timestamps as $timestamp ) {
							if ( intval( $timestamp ) === Groups_WS_Terminator::ETERNITY ) {
								$ts = Groups_WS_Terminator::ETERNITY;
								break;
							} else {
								if ( $timestamp > $ts ) {
									$ts = $timestamp;
								}
							}
						}
						if ( $ts !== null ) {
							if ( $ts === Groups_WS_Terminator::ETERNITY ) {
								$membership_info = sprintf(
									__( '<em>%s</em> membership.', GROUPS_WS_PLUGIN_DOMAIN ),
									wp_filter_nohtml_kses( $group->name )
								);
							} else {
								$date = date_i18n( get_option( 'date_format' ), $ts );
								$time = date_i18n( get_option( 'time_format' ), $ts );
								$membership_info = sprintf(
									__( '<em>%1$s</em> membership until %2$s at %3$s.', GROUPS_WS_PLUGIN_DOMAIN ),
									wp_filter_nohtml_kses( $group->name ),
									$date,
									$time
								);
							}
						}
						echo apply_filters( 'groups_woocommerce_show_buckets_membership', $membership_info, $group_id, $ts );
	
						if ( GROUPS_WS_LOG ) {
							echo '<ul>';
							foreach( $timestamps as $timestamp ) {
								echo '<li>';
								if ( intval( $timestamp ) === Groups_WS_Terminator::ETERNITY ) {
									echo __( 'Unlimited', GROUPS_WS_PLUGIN_DOMAIN );
								} else {
									echo date( 'Y-m-d H:i:s', $timestamp );
								}
								
								echo '</li>';
							}
							echo '<ul>';
						}
	
						echo '</li>';
					}
				}
			}
			echo '</ul>';
		}
	}

	/**
	 * Sort helper - comparison by group name for given group ids.
	 * @param int $group_id1
	 * @param int $group_id2
	 * @return int
	 */
	public static function bucket_cmp( $group_id1, $group_id2 ) {
		$result = 0;
		if ( $g1 = Groups_Group::read( $group_id1 ) ) {
			if ( $g2 = Groups_Group::read( $group_id2 ) ) {
				if ( isset( $g1->name ) && isset( $g2->name ) ) {
					$result = strcmp( $g1->name, $g2->name );
				}
			}
		}
		return $result;
	}
}
Groups_WS_User::init();
