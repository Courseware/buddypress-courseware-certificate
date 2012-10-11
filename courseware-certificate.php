<?php
/*
Plugin Name: BuddyPress Courseware Certificate
Plugin URI: http://wordpress.org/extend/plugins/buddypress-courseware-certificate/
Description: Issue a certificate upon completing BuddyPress Courseware assignments.
Version: 0.1
Author: sushkov
Author URI: http://wordpress.org/extend/plugins/buddypress-courseware-certificate/
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
 * Courseware Certificate Class
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

      $completed = true;

      foreach( $group_assignments as $group_assignment ) {
        // Stop if a response is missing
        if ( !$completed ) {
          break;
        }

        $responded = get_post_meta( $group_assignment->ID, 'responded_author' );
        if ( !in_array( $response->post_author, $responded ) ) {
          $completed = false;
        }
      }

      if ( $completed && $passed ) {
        self::generate_certificate( $response->post_author, $group_id );
      }
    }

    /**
     * Generates the pdf
     */
    function save_pdf( $svg, $user ) {
      $tmp_pdf = sys_get_temp_dir() . '/certificate.pdf';
      $pdf = new TCPDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

      $pdf->SetCreator( PDF_CREATOR );
      $pdf->SetAuthor( 'BuddyPress Courseware' );
      $pdf->SetTitle( 'Certificate for ' . $user->display_name );

      $pdf->SetMargins( 0, 0, 0 );
      $pdf->SetHeaderMargin(0);
      $pdf->SetFooterMargin(0);
      $pdf->AddPage();
      $pdf->ImageSVG(
        $file=$svg,
        $x=0,
        $y=0,
        $w='',
        $h='',
        $link='',
        $align='',
        $palign='',
        $border=0,
        $fitonpage=false
      );
      $pdf->Output( $tmp_pdf, 'F' );

      return $tmp_pdf;
    }

    /**
     * Parses the svg file
     */
    function prepare_pdf( $user, $group ) {
      $original_svg = get_stylesheet_directory() . '/certificate.svg';

      if ( !file_exists( $original_svg ) ) {
        $original_svg = dirname( __FILE__ ) . '/svg/certificate.svg';
      }

      $tmp_svg = sys_get_temp_dir() . '/certificate.svg';

      $svg = file_get_contents( $original_svg );

      $svg = str_replace( 'USER_FULL_NAME', $user->display_name, $svg );
      $svg = str_replace( 'GROUP_FULL_NAME', $group->name, $svg );
      $svg = str_replace( 'GROUP_DESCRIPTION', $group->description, $svg );
      $svg = str_replace( 'GROUP_URL', get_site_url(), $svg );
      $svg = str_replace( 'FULL_DATE', bpsp_get_date( date('Y-m-d H:i:s' ) ), $svg );

      file_put_contents( $tmp_svg, $svg );

      return self::save_pdf( $tmp_svg, $user );
    }

    /**
     * Emails the certificate
     */
    function email_certificate( $email, $pdf ) {
      wp_mail( $email, 'BuddyPress Courseware Certificate', '', '', array( $pdf ) );
    }

    /**
     * Generates the certificate
     */
    function generate_certificate( $user_id, $group_id ) {
      $group = groups_get_group( array( 'group_id' => $group_id ) );
      $user = reset( get_users( array( 'include' => $user_id ) ) );

      $certificate = self::prepare_pdf( $user, $group );
      if ( self::email_certificate( $user->user_email, $certificate ) ) {
        unlink( $certificate );
        unlink( str_replace( '.pdf', '.svg', $certificate ) );
      }
    }
}
CoursewareCertificate::init();
?>
