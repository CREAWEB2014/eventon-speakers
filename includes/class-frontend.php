<?php
/**
 * Event SS front end class
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	EventON-SS/classes
 * @version     0.7
 */
class evoss_front{
	
	function __construct(){
		global $evo_speak;

		$this->evopt1 = get_option('evcal_options_evcal_1');
		$this->evopt2 = get_option('evcal_options_evcal_2');

		include_once('class-functions.php');
		$this->functions = new evoss_functions();

		// scripts and styles 
		add_action( 'init', array( $this, 'register_styles_scripts' ) ,15);	

		add_filter('eventon_eventCard_evospk', array($this, 'frontend_box'), 10, 2);
		add_filter('eventon_eventCard_evosch', array($this, 'frontend_box_sch'), 10, 2);
		add_filter('eventon_eventcard_array', array($this, 'eventcard_array'), 10, 4);
		add_filter('evo_eventcard_adds', array($this, 'eventcard_adds'), 10, 1);

		//add_action( 'wp_footer', array( $this, 'footer_code' ) ,15);
		add_filter('evo_frontend_lightbox', array($this, 'ligthbox'),10,1);

		$this->opt2 = $evo_speak->opt2;
		add_action('evo_addon_styles', array($this, 'styles') );

		// filter support
		add_action('eventon_so_filters', array($this, 'include_speaker'),10,1);
		add_filter('eventon_extra_tax',array($this,'include_speaker'),10,1);
	}

	// include event speaker support for filtering options
		function include_speaker($_filter_array){
			$_filter_array['evspk']= 'event_speaker';
			return $_filter_array;
		}

	
	// frontend box
		function frontend_box($object, $helpers){
			global $evo_speak;
			
			$speaker_terms = wp_get_post_terms($object->event_id, 'event_speaker');

			if ( !$speaker_terms && is_wp_error( $speaker_terms ) ) return false;
			if( count($speaker_terms)==0 ) return false;

			$termMeta = get_option( "evo_tax_meta");

			ob_start();
			$opt = $this->evopt2;
			echo  "<div class='evo_metarow_speaker evorow evcal_evdata_row bordb evcal_evrow_sm".$helpers['end_row_class']."' data-event_id='".$object->event_id."'>
					<span class='evcal_evdata_icons'><i class='fa ".get_eventON_icon('evcal__evospk_001', 'fa-coffee',$helpers['evOPT'] )."'></i></span>
					<div class='evcal_evdata_cell'>";
				echo "<h3 class='evo_h3'>".eventon_get_custom_language($opt, 'evoss_001','Speakers for this event')."</h3>";

				$termMeta = get_option( "evo_tax_meta");
				echo '<ul class="evospk_boxes">';
				foreach($speaker_terms as $speaker){
					$termmeta = evo_get_term_meta('event_speaker',$speaker->term_id, $termMeta);

					// image
					$img_url = $evo_speak->assets_path.'speaker.jpg';
					if(!empty($termmeta['evo_spk_img'])){
						$img_url = wp_get_attachment_image_src($termmeta['evo_spk_img'],'medium');
						$img_url = $img_url[0];
					}
					?>					
						<li class="evospk_box">
							<span class='evospk_img_box'>
								<span class="evospk_img" style='background-image: url(<?php echo $img_url;?>)'></span>
							</span>
							<h3 class="evo_h3"><?php echo $speaker->name;?></h3>
								<div class='evospk_hidden'>
									<div class="evospk_img">
										<span style='background-image: url(<?php echo $img_url;?>)'></span></div>
									<div class="evospk_info">
										<h2><?php echo $speaker->name;?></h2>
										<?php 
											// title
											if(!empty($termmeta['evo_speaker_title']))
												echo "<p class='evo_speaker_title'>".$termmeta['evo_speaker_title'].'</p>';

											// description
											if(!empty($speaker->description))
												echo "<p class='evo_speaker_desc'>".$speaker->description.'</p>';

											$social = $other = false;
											foreach($evo_speak->functions->speaker_fields() as $key=>$val){
												//print_r($key);
												if(in_array($key, array('evo_spk_img','evo_speaker_name','evo_speaker_title','evo_speaker_desc'))) continue;

												if(empty($termmeta[$key])) continue;

												if(in_array($key, array('evoss_fb','evoss_tw','evoss_ln','evoss_ig'))){
													$social.="<a target='_blank' href='".$termmeta[$key]."' class='fa fa-".strtolower($val[1])."'></a>";
												}else{// all other extra fields
													$other.= "<p class='{$key} extra'><em>".$val[1].'</em> '.$termmeta[$key].'</p>';
												}												
											}

											// social media
												if($social){
													echo "<p class='evo_speaker_social'>".$social.'</p>';
												}
												echo $other;
										?>
									</div>
								</div><!-- hidden section-->
							
							<?php if(!empty($termmeta['evo_speaker_title'])):?>
								<p class='evospk_job_title'><?php echo $termmeta['evo_speaker_title'];?></p>
							<?php endif;?>
							
						</li>					
					<?php

				}
				echo '</ul>';
				echo '<div class="clear"></div>';
				echo "</div>".$helpers['end'];
			echo "</div>";
							
			return ob_get_clean();
		}

		
		function frontend_box_sch($object, $helpers){
			global $evo_speak;
			$opt = $this->evopt2;
			$ev_vals = $object->pmv;
			$blocks = !empty($ev_vals['_sch_blocks'])? unserialize($ev_vals['_sch_blocks'][0]): false;

			if(!$blocks) return false;

			ob_start();
			echo  "<div class='evo_metarow_schedule evorow evcal_evdata_row bordb evcal_evrow_sm".$helpers['end_row_class']."' data-event_id='".$object->event_id."'>
					<span class='evcal_evdata_icons'><i class='fa ".get_eventON_icon('evcal__evosch_001', 'fa-calendar-check-o',$helpers['evOPT'] )."'></i></span>
					<div class='evcal_evdata_cell'>";
				echo "<h3 class='evo_h3'>".eventon_get_custom_language($opt, 'evoss_002','Schedule')."</h3>";				
				//print_r($blocks);

				if($blocks){
					$nav = '';
					echo "<ul class='evosch_blocks_list'>";
					$nav = '';
					$content = '';

					//ksort($blocks);
					$block_count = 1;

					for($v=1; $v<=100; $v++){
					//foreach($blocks as $day_=>$block){

						$day_ = 'd'.$v;
						if( empty($blocks[$day_])) continue;
						$block = $blocks[$day_];

						if(count($block)==1) continue;
						$day = (int)substr($day_, 1);

						if(isset($block[0])) 
							$nav .= "<li class='".( $block_count==1?'evoss_show':'')."' data-day='{$day}' title='".$block[0]."'>".eventon_get_custom_language($opt,'evoss_003','Day')." ".$day."</li>";
							
						//$content .= "<li><p class='evosch_day'>Day {$day}</p>";
						$content .= "<ul class='evosch_oneday_schedule ".($block_count==1?'evoss_show':'')." evosch_date_{$day}'>";

						$count = 1;
						foreach($block as $key=>$data){
							if($key==0) continue;

							// first item on the date
							if($count ==1){
								$content .= "<li class='date'>". date_i18n(get_option( 'date_format' ),strtotime($block[0]))."</li>";
							}

							$content .= "<li>";
							
							$desc_visibile = ($count==1)? 'evoss_show':'evoss_hide';
							$content .= "<p><em class='time'>".$data['evo_sch_stime']."</em> 
								<span><b class='{$desc_visibile}'>".$data['evo_sch_title']."</b><span class='{$desc_visibile}'><i>".$data['evo_sch_stime'].' - '.$data['evo_sch_etime']."</i>".$data['evo_sch_desc'];
								
								// speakers
								if( !empty($data['evo_sch_spk'])  ){
									$content .="<u>" . evo_lang('Speakers'). ": ";
									$count = 1;
									foreach($data['evo_sch_spk'] as $spk){
										$comma = (count($data['evo_sch_spk'])>1 && $count!= count($data['evo_sch_spk']))?
											', ':'';
										$content .= '<em class="speaker">'.$spk.$comma.'</em>';
										$count ++;
									}
									$content .="</u>";
								}
							$content .= '</span>';
							$content .= "</span>
								</p>";
							$content .= "</li>";
							$count++;
						}	
						$content .= "</ul>";
						$content .= "</li>";
						$block_count++;
					}

					// build nav
						
					echo "<ul class='evosch_nav'>".$nav."</ul>". $content;
					echo "</ul>";
				}

				echo "</div>".$helpers['end'];
			echo "</div>";
							
			return ob_get_clean();

		}
		function eventcard_array($array, $pmv, $eventid, $__repeatInterval){
			$array['evospk']= array(
				'event_id' => $eventid,
				'pmv'=>$pmv,
				'__repeatInterval'=>(!empty($__repeatInterval)? $__repeatInterval:0)
			);$array['evosch']= array(
				'event_id' => $eventid,
				'pmv'=>$pmv,
				'__repeatInterval'=>(!empty($__repeatInterval)? $__repeatInterval:0)
			);
			return $array;
		}
		function eventcard_adds($array){
			$array[] = 'evospk';
			$array[] = 'evosch';
			return $array;
		}

	// STYLES:  
		public function register_styles_scripts(){
			if(is_admin()) return false;
			global $evo_speak;		

			if( evo_settings_val('evcal_concat_styles',$this->evopt1, true))	
				wp_register_style( 'evoss_styles',$evo_speak->assets_path.'SS_styles.css');
			
			wp_register_script('evoss_script',$evo_speak->assets_path.'SS_script.js', array('jquery'), $evo_speak->version, true );
			wp_localize_script( 
				'evoss_script', 
				'evoss_ajax_script', 
				array( 
					'evoss_ajaxurl' => admin_url( 'admin-ajax.php' ) , 
					'postnonce' => wp_create_nonce( 'evoss_nonce' )
				)
			);
			
			$this->print_scripts();
			add_action( 'wp_enqueue_scripts', array($this,'print_styles' ));				
		}
		public function print_scripts(){	wp_enqueue_script('evoss_script');		}
		function print_styles(){	wp_enqueue_style( 'evoss_styles');	}
		//	Styles for the tab page
		function styles(){
			global $evo_speak;
			ob_start();
			include_once($evo_speak->plugin_path.'/assets/SS_styles.css');
			echo ob_get_clean();
		}

	// footer
		function ligthbox($array){
			$array['evoss_lightbox']= array(
				'id'=>'evoss_lightbox',
				'CLclosebtn'=> 'evoss_lightbox',
			);return $array;
		}
		
	// SUPPORT functions
		
		// RETURN: language
			function lang($variable, $default_text){
				global $evo_speak;
				return $evo_speak->lang($variable, $default_text);
			}
		
}
