<?php
/**
 * Adds ProMissa_MassTimes_Widget widget.
 */

class ProMissa_Corona_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct()
	{
		$widget_ops = array('classname' => 'promissa_widget_masses promissa_corona', 'description' => __( 'This widget will show the upcoming mass times. This is recommended if your primary target audience are seekers to faith.', 'promissa' ));
        parent::__construct(
        	'promissa_widget_corona', // Base ID
        	__( 'Upcoming Masses', 'promissa' ) . ' (Pro Missa)', // Name
        	$widget_ops // Args
        );
	}

	public static function createShortcode($instance)
	{
		$subtitle = '';
		$limit = '';
		$page = '';
		$show_title = '';
		$show_attendees = '';
		if ( ! empty( $instance['subtitle'] ) ) :
			$subtitle = sprintf(' subtitle="%s"', $instance['subtitle']);
		endif;
		if ( ! empty( $instance['limit'] ) ) :
			$limit = sprintf(' limit="%s"', $instance['limit']);
		endif;
		if ( ! empty( $instance['page'] ) ) :
			$page = sprintf(' page="%s"', $instance['page']);
		endif;
		if ( ! empty( $instance['show_title'] ) ) :
			$show_title = sprintf(' show_title="%s"', (((int)$instance['show_title']) == 1 ? 'true' : 'false'));
		endif;
		if ( ! empty( $instance['show_attendees'] ) ) :
			$show_attendees = sprintf(' show_attendees="%s"', (((int)$instance['show_attendees']) == 1 ? 'true' : 'false'));
		endif;
		if(isset($instance['church_ID']) && !empty($instance['church_ID'])) :
			return '[promissa-corona church_id="' . $instance['church_ID'] . '"' . $subtitle . $limit . $page . $show_title . $show_attendees . ']';
		else :
			return '[promissa-corona' . $subtitle. $limit . $page . $show_title . $show_attendees . ']';
		endif;
		return '';
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) :
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		endif;
		echo do_shortcode( ProMissa_Corona_Widget::createShortcode($instance) );
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		global $wpdb;
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Next Masses', 'promissa' );
		$subtitle = ! empty( $instance['subtitle'] ) ? $instance['subtitle'] : '';
		$church_ID = ! empty( $instance['church_ID'] ) ? $instance['church_ID'] : '0';
		$limit = ! empty( $instance['limit'] ) ? $instance['limit'] : 10;
		$page = ! empty( $instance['page'] ) ? $instance['page'] : 0;
		$show_title = ! empty( $instance['show_title'] ) ? $instance['show_title'] : 0;
		$show_attendees = ! empty( $instance['show_attendees'] ) ? $instance['show_attendees'] : 0;
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'subtitle' ); ?>"><?php _e( 'Subtitle:', 'promissa' ); ?></label>
		<textarea class="widefat" rows="5" id="<?php echo $this->get_field_id( 'subtitle' ); ?>" name="<?php echo $this->get_field_name( 'subtitle' ); ?>"><?php echo esc_attr( $subtitle ); ?></textarea>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'church_ID' ); ?>"><?php _e( 'Church:', 'promissa' ); ?></label>
		<?php
			echo '<select class="widefat" id="' . $this->get_field_id( 'church_ID' ) . '" name="' . $this->get_field_name( 'church_ID' ) . '">';
			$churches = ProMissaREST('Churches');
			echo '<option value="0"' . (esc_attr( $church_ID ) == '0' ? ' selected="selected"' : '') . '>' . __( 'All churches:', 'promissa' ) . '</option>';

			foreach($churches as $church) {
				echo '<option value="' . $church['ID'] . '"' . (esc_attr( $church_ID ) == $church['ID'] ? ' selected="selected"' : '') . '>' . $church['title'] . '</option>';
			}
			echo '</select>';
		?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Count:', 'promissa' ); ?></label>
		<?php
			echo '<select class="widefat" id="' . $this->get_field_id( 'limit' ) . '" name="' . $this->get_field_name( 'limit' ) . '">';

			for($i = 1; $i <=15; $i++) {
				echo '<option value="' . $i . '"' . ((int)esc_attr( $limit ) == $i ? ' selected="selected"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';
		?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'page' ); ?>"><?php _e( 'Page:', 'promissa' ); ?></label>
		<?php
			echo '<select class="widefat" id="' . $this->get_field_id( 'page' ) . '" name="' . $this->get_field_name( 'page' ) . '">';

			for($i = 1; $i <=15; $i++) {
				echo '<option value="' . $i . '"' . ((int)esc_attr( $page ) == $i ? ' selected="selected"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';
		?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Show title:', 'promissa' ); ?></label>
		<?php
			echo '<select class="widefat" id="' . $this->get_field_id( 'show_title' ) . '" name="' . $this->get_field_name( 'show_title' ) . '">';

			for($i = 1; $i >= 0; $i--) {
				echo '<option value="' . $i . '"' . ((int)esc_attr( $show_title ) == $i ? ' selected="selected"' : '') . '>' . ($i == 0 ? __( 'No', 'promissa' ): __( 'Yes', 'promissa' )) . '</option>';
			}
			echo '</select>';
		?>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'show_attendees' ); ?>"><?php _e( 'Show attendees:', 'promissa' ); ?></label>
		<?php
			echo '<select class="widefat" id="' . $this->get_field_id( 'show_attendees' ) . '" name="' . $this->get_field_name( 'show_attendees' ) . '">';

			for($i = 1; $i >= 0; $i--) {
				echo '<option value="' . $i . '"' . ((int)esc_attr( $show_attendees ) == $i ? ' selected="selected"' : '') . '>' . ($i == 0 ? __( 'No', 'promissa' ): __( 'Yes', 'promissa' )) . '</option>';
			}
			echo '</select>';
		?>
		</p>
		<code>
			<?php
				echo ProMissa_Corona_Widget::createShortcode($instance);
			?>
		</code>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['subtitle'] = ( ! empty( $new_instance['subtitle'] ) ) ? strip_tags( $new_instance['subtitle'] ) : '';
		$instance['church_ID'] = ( ! empty( $new_instance['church_ID'] ) ) ? strip_tags( $new_instance['church_ID'] ) : '';
		$instance['limit'] = ( ! empty( $new_instance['limit'] ) ) ? strip_tags( $new_instance['limit'] ) : 10;
		$instance['page'] = ( ! empty( $new_instance['page'] ) ) ? strip_tags( $new_instance['page'] ) : 0;
		$instance['show_title'] = ( ! empty( $new_instance['show_title'] ) ) ? strip_tags( $new_instance['show_title'] ) : 0;
		$instance['show_attendees'] = ( ! empty( $new_instance['show_attendees'] ) ) ? strip_tags( $new_instance['show_attendees'] ) : 0;
		return $instance;
	}

} // class ProMissa_Corona_Widget

?>