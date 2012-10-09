<?php
/*
Plugin Name: Courseware Certificate
Plugin URI: http://wordpress.org/extend/plugins/courseware-certificate/
Description: Issue a certificate upon completing BuddyPress Courseware assignments.
Version: 0.1
Author: sushkov
Author URI: http://wordpress.org/extend/plugins/courseware-certificate/
*/
?>
<?php
/*  Copyright 2012  Stas Suscov <stas@net.utcluj.ro>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'COURSEWARE_CERTIFICATE', '0.1' );

/**
 * Main Courseware-certificate Class
 */
class CoursewareCertificate {
    /**
     * Static constructor
     */
    function init() {
      add_action( 'courseware_response_added', array( __CLASS__, 'issue_certificate' ) );
    }

    /**
     * Checks if users assignments are complete and issues a certificate if so
     */
    function issue_certificate( $vars ) {
      $assignment = $vars['assignment'];
      $response = $vars['response'];

      // Suppose any previous response is passed
      $passed = $response->form_values['correct'] >= $response->form_values['total'] / 2;
      $group_id = wp_get_object_terms( $assignment->ID, 'group_id' );
      $group_id = reset( $group_id )->slug;

      $group_assignments = BPSP_Assignments::has_assignments( $group_id );
      $grades = array();

      $completed = true;

      foreach( $group_assignments as $group_assignment ) {
        // Stop if a response is missing
        if ( !$completed ) {
          break;
        }

        $grades[] = BPSP_Gradebook::load_grade_by_user_id(
          $group_assignment->ID, $response->post_author
        );

        $responded = get_post_meta( $group_assignment->ID, 'responded_author' );
        if ( !in_array( $response->post_author, $responded ) ) {
          $completed = false;
        }
      }

      if ( $completed && $passed ) {
        self::generate_certificate( $response->post_author, $group_id, $grades );
      }
    }

    /**
     * Generate and email the certificate
     */
    function generate_certificate( $user_id, $group_id, $grades ) {
      $group = groups_get_group( array( 'group_id' => $group_id ) );
      $user = get_users( array( 'include' => $user_id ) );
      var_dump(
        $user,
        $group,
        $grades
      );
      die();
      // TODO: generate certificate in png/pdf
    }

}
CoursewareCertificate::init();
?>
