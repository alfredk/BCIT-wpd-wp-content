<?php

class FUE_Sensei {

    public function __construct() {
        add_filter( 'fue_trigger_types', array($this, 'trigger_types'), 10, 2 );
        add_filter( 'fue_email_types', array($this, 'email_types') );
        add_filter( 'fue_email_type_long_descriptions', array($this, 'email_type_long_descriptions') );
        add_filter( 'fue_email_type_short_descriptions', array($this, 'email_type_short_descriptions') );
        add_filter( 'fue_email_type_triggers', array($this, 'email_type_triggers') );

        add_filter( 'fue_email_type_is_valid', array($this, 'valid_type'), 10, 2 );

        add_action( 'fue_email_form_script', array($this, 'add_script') );

        // course
        add_action( 'sensei_user_course_start', array($this, 'course_sign_up'), 10, 2 );
        add_action( 'sensei_user_course_end', array($this, 'course_completed'), 10, 2 );

        // lesson
        add_action( 'sensei_user_lesson_start', array($this, 'lesson_start'), 10, 2 );
        add_action( 'sensei_user_lesson_end', array($this, 'lesson_end'), 10, 2 );

        // quiz score
        add_action( 'sensei_user_quiz_grade', array($this, 'quiz_grade'), 10, 4 );

        // email form variables
        add_action( 'fue_email_variables_list', array($this, 'email_variables_list') );

        // variable replacements
        add_filter( 'fue_email_sensei_variables', array($this, 'email_variables'), 10, 4 );
        add_filter( 'fue_email_sensei_replacements', array($this, 'email_replacements'), 10, 4 );

    }

    public function trigger_types( $triggers = array(), $email_type = '' ) {
        $triggers['specific_answer']        = __('after selecting a specific answer', 'follow_up_emails');
        $triggers['course_signup']          = __('after signed up to a course', 'follow_up_emails');
        $triggers['course_completed']       = __('after course is completed', 'follow_up_emails');
        $triggers['lesson_start']           = __('after lesson is started', 'follow_up_emails');
        $triggers['lesson_completed']       = __('after lesson is completed', 'follow_up_emails');
        $triggers['quiz_completed']         = __('after completing a quiz', 'follow_up_emails');
        $triggers['quiz_passed']            = __('after passing a quiz', 'follow_up_emails');
        $triggers['quiz_failed']            = __('after failing a quiz', 'follow_up_emails');

        return $triggers;

    }

    public function email_types( $types ) {
        $types['sensei']    = __('Sensei Email', 'follow_up_emails');

        return $types;
    }

    public function email_type_long_descriptions( $descriptions ) {
        $descriptions['sensei']    = __('Sensei emails will send to a user based upon the quiz/course/lesson/test status you define when creating your emails. Below are the existing Sensei emails set up for your store. Use the priorities to define which emails are most important. These emails are selected first when sending the email to the customer if more than one criteria is met by multiple emails. Only one email is sent out to the customer (unless you enable the Always Send option when creating your emails), so prioritizing the emails for occasions where multiple criteria are met ensures you send the right email to the right customer at the time you choose.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_short_descriptions( $descriptions ) {
        $descriptions['sensei']    = __('Sensei emails will send to a user based upon the quiz/course/lesson/test status you define when creating your emails.', 'follow_up_emails');

        return $descriptions;
    }

    public function email_type_triggers( $type_triggers ) {
        $type_triggers['sensei'] = array(
            'specific_answer', 'course_signup', 'course_completed',
            'lesson_start', 'lesson_completed',
            'quiz_completed', 'quiz_passed', 'quiz_failed'
        );

        return $type_triggers;
    }

    public function valid_type($valid, $data) {
        if ($data['type'] == 'sensei') {
            $valid = true;
        }

        return $valid;
    }

    public function add_script() {
        ?>
        jQuery("body").bind("fue_email_type_changed", function(evt, type) {
            sensei_toggle_fields( type );
        });

        jQuery("body").bind("fue_interval_type_changed", function(evt, type) {
            //sensei_toggle_interval_type_fields( type );
        });

        function sensei_toggle_interval_type_fields( type ) {
            var show = [];
            var hide = ['.course_tr', '.lesson_tr'];

            switch (type) {
                case 'course_signup':
                case 'course_completed':
                    show = ['.course_tr'];
                    break;

                case 'lesson_signup':
                case 'lesson_completed':
                    show = ['.lesson_tr'];
                    break;
            }

            for (x = 0; x < hide.length; x++) {
                jQuery(hide[x]).hide();
            }

            for (x = 0; x < show.length; x++) {
                jQuery(show[x]).show();
            }

        }

        function sensei_toggle_fields( type ) {
            if (type == "sensei") {
                var val  = jQuery("#interval_type").val();
                var show = [];
                var hide = ['.interval_type_option', '.always_send_tr', '.signup_description', '.product_description_tr', '.product_tr', '.category_tr', '.use_custom_field_tr', '.custom_field_tr', '.var_item_name', '.var_item_category', '.var_item_names', '.var_item_categories', '.var_item_name', '.var_item_category', '.interval_type_after_last_purchase', '.interval_duration_date', '.var_customer', '.var_order'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }

                for (x = 0; x < show.length; x++) {
                    jQuery(show[x]).show();
                }

                // triggers
                jQuery(".interval_type_option").remove();

                if ( email_intervals && email_intervals.sensei.length > 0 ) {
                    for (var x = 0; x < email_intervals.sensei.length; x++) {
                        var int_key = email_intervals.sensei[x];
                        jQuery("#interval_type").append('<option class="interval_type_option interval_type_'+ int_key +'" id="interval_type_option_'+ int_key +'" value="'+ int_key +'">'+ interval_types[int_key] +'</option>');
                    }

                    jQuery("#interval_type").val(val);
                }

                jQuery(".interval_duration_date").hide();

                jQuery("#interval_type").change();
            } else {
                var hide = ['.course_tr'];

                for (x = 0; x < hide.length; x++) {
                    jQuery(hide[x]).hide();
                }
            }
        }

        jQuery(document).ready(function() {
            sensei_toggle_fields( jQuery("#email_type").val() );
        });
        <?php
    }

    public function course_sign_up( $user_id, $course_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'course_signup' AND status = 1");

        foreach ( $emails as $email ) {
            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('course_id' => $course_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function course_completed( $user_id, $course_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'course_completed'");
        $user   = new WP_User($user_id);

        foreach ( $emails as $email ) {
            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('course_id' => $course_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function lesson_start( $user_id, $lesson_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'lesson_start' AND status = 1");
        $user   = new WP_User($user_id);

        foreach ( $emails as $email ) {
            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('lesson_id' => $lesson_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function lesson_end( $user_id, $lesson_id ) {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type = 'lesson_completed' AND status = 1");
        $user   = new WP_User($user_id);

        foreach ( $emails as $email ) {
            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('lesson_id' => $lesson_id)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function quiz_grade( $user_id, $quiz_id, $grade, $passmark ) {
        global $wpdb;

        $types = "'quiz_completed'";

        if ( $grade >= $passmark ) {
            $types .= ",'quiz_passed'";
        } else {
            $types .= ",'quiz_failed'";
        }

        $emails = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}followup_emails WHERE interval_type IN ($types) AND status = 1");
        $user   = new WP_User($user_id);

        foreach ( $emails as $email ) {
            $values = array(
                'user_id'   => $user_id,
                'meta'      => array('quiz_id' => $quiz_id, 'grade' => $grade, 'passmark' => $passmark)
            );

            FUE::queue_email( $values, $email );
        }
    }

    public function email_variables_list( $defaults ) {
        switch ( $defaults['interval_type'] ) {

            case 'course_signup':
            case 'course_completed':
                echo '<li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                break;

            case 'lesson_start':
            case 'lesson_completed':
                echo '<li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_lesson"><strong>{lesson_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                break;

            case 'quiz_completed':
            case 'quiz_passed':
            case 'quiz_failed':
                echo '<li class="var hideable var_sensei var_sensei_course"><strong>{course_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_lesson"><strong>{lesson_name}</strong> <img class="help_tip" title="'. __('The name of the course', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_grade"><strong>{quiz_grade}</strong> <img class="help_tip" title="'. __('The score the user got on the quiz', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                echo '<li class="var hideable var_sensei var_sensei_passmark"><strong>{quiz_passmark}</strong> <img class="help_tip" title="'. __('The passing mark on the quiz taken', 'follow_up_emails') .'" src="'. FUE_TEMPLATES_URL .'/images/help.png" width="16" height="16" /></li>';
                break;
        }

    }

    public function email_variables( $vars, $email_data, $email_order, $email ) {
        switch ( $email->interval_type ) {

            case 'course_signup':
            case 'course_completed':
                $vars[] = '{course_name}';
                break;

            case 'lesson_signup':
            case 'lesson_completed':
                $vars[] = '{course_name}';
                $vars[] = '{lesson_name}';
                break;

            case 'quiz_completed':
            case 'quiz_passed':
            case 'quiz_failed':
                $vars[] = '{course_name}';
                $vars[] = '{lesson_name}';
                $vars[] = '{quiz_grade}';
                $vars[] = '{quiz_passmark}';
                break;

        }

        return $vars;
    }

    public function email_replacements( $replacements, $email_data, $email_order, $email ) {

        if ( $email->email_type == 'sensei' ) {

            $meta = maybe_unserialize( $email_order->meta );

            if ( $email->interval_type == 'course_signup' || $email->interval_type == 'course_completed' ) {
                $replacements[] = get_the_title( $meta['course_id'] );
            } elseif ( $email->interval_type == 'lesson_signup' || $email->interval_type == 'lesson_completed' ) {
                $course_id      = get_post_meta( $meta['lesson_id'], '_lesson_course', true );
                $replacements[] = get_the_title( $course_id );
                $replacements[] = get_the_title( $meta['lesson_id'] );
            } elseif ( $email->interval_type == 'quiz_completed' || $email->interval_type == 'quiz_passed' || $email->interval_type == 'quiz_failed' ) {
                $lesson_id      = get_post_meta( $meta['quiz_id'], '_quiz_lesson', true );
                $course_id      = get_post_meta( $meta['quiz_id'], '_lesson_course', true );
                $replacements[] = get_the_title( $course_id );
                $replacements[] = get_the_title( $lesson_id );
                $replacements[] = $meta['grade'];
                $replacements[] = $meta['passmark'];
            }

        }

        return $replacements;
    }

}

$GLOBALS['fue_sensei'] = new FUE_Sensei();
