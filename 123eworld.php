<?php
/*
 Plugin Name: 123eworld SMS
 Plugin URI: 
 Description: 123eworld SMS Service Integration
 Version: 1.0.0
 Author: 123eworld
 Author URI: http://www.123eworld.com
 Text Domain: 123eworld
 */
if ( ! defined('ABSPATH')) exit;  // if direct access

$plugin_dir_name = dirname(plugin_basename( __FILE__ )); 

define("EWORLD_SMS_DIR", WP_PLUGIN_DIR."/".$plugin_dir_name);

class EworldSMS{
	/**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

	public function __construct(){
  		$this->options = get_option( '123eworld_sms_option' );		
		if( is_admin() )
		{
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action( 'admin_init', array($this, 'admin_init'));
		}
		add_action("wp_ajax_eworld_send_sms", array($this, "sendAjaxSMS"));
		add_action('eworld_send_sms', array( $this, 'sendSMS' ), 10, 3 );
		// Work if user is not login
		//add_action("wp_ajax_nopriv_send_sms", array($this, "sendAjaxSMS"));
	}
	
	public function admin_menu()
    {
    	add_menu_page(__('123eworld SMS',"123eworld"), __('123eworld SMS',"123eworld"), 'manage_options', '123eworld_sms', array($this, 'settings'), 'dashicons-email-alt' );
		add_submenu_page('123eworld_sms',__('Send SMS',"123eworld"),__('Send SMS',"123eworld"),'manage_options','send_sms', array($this, 'send_sms'));
	}

	public function admin_init()
    {        
        register_setting(
            '123eworld_sms_options', // Option group 
            '123eworld_sms_option', // Option name
            array( $this, 'option_sanitize' ) // Sanitize
        );

        add_settings_section(
            'sms_api_setting', // ID
            'API Credentials', // Title
            '',
            //array( $this, 'print_section_info' ), // Callback
            '123eworld_sms_settings' // Page
        );  

        add_settings_field(
            'user_name', // ID
            'User Name', // Title 
            array( $this, 'user_name_callback' ), // Callback
            '123eworld_sms_settings', // Page
            'sms_api_setting' // Section           
        );

        add_settings_field(
            'password', // ID
            'Password', // Title 
            array( $this, 'password_callback' ), // Callback
            '123eworld_sms_settings', // Page
            'sms_api_setting' // Section           
        );
        add_settings_field(
            'senderid', 
            'Sender ID', 
            array( $this, 'senderid_callback' ), 
            '123eworld_sms_settings', 
            'sms_api_setting'
        );
    }

    public function option_sanitize( $data )
    {
        $sanitized_data = array();
        if( isset( $data['user_name'] ) )
            $sanitized_data['user_name'] = sanitize_text_field( $data['user_name'] );

        if( isset( $data['password'] ) )
            $sanitized_data['password'] = sanitize_text_field( $data['password'] );
			
        if( isset( $data['senderid'] ) )
            $sanitized_data['senderid'] = sanitize_text_field( $data['senderid'] );

        return $sanitized_data;
    }

 
    public function user_name_callback()
    {
        printf(
            '<input type="text" id="user_name" name="123eworld_sms_option[user_name]" size="50" value="%s" />',
            isset( $this->options['user_name'] ) ? esc_attr( $this->options['user_name']) : ''
        );
    }
 
    public function password_callback()
    {
        printf(
            '<input type="text" id="password" name="123eworld_sms_option[password]" size="50" value="%s" />',
            isset( $this->options['password'] ) ? esc_attr( $this->options['password']) : ''
        );
    }
    public function senderid_callback()
    {
        printf(
            '<input type="text" id="senderid" name="123eworld_sms_option[senderid]" size="50" value="%s" />',
            isset( $this->options['senderid'] ) ? esc_attr( $this->options['senderid']) : ''
        );
    }
	
	public function settings()
  	{
  		?>
		<div class="wrap">
			<h2><?php _e('123eworld SMS API Settings',"123eworld");?></h2>
			<form method="post" action="options.php">
				<?php
					settings_fields( '123eworld_sms_options' );
					do_settings_sections( '123eworld_sms_settings' );
					submit_button();
				?>
			</form>
			<?php $this->showHelp();?>
		</div>
		<?php
  	}
	
	public function send_sms()
	{
		$Msg = "";
		$MsgType = "success";
		if (isset($_POST['send_sms']))
		{
			if (trim($_POST['mobile_no']) == "" || trim($_POST['message']) == "")
			{
				$Msg = __("Both Mobile number and Message are required","123eworld");
				$MsgType = "error";
			}
			else
			{
				$Msg = $this->sendSMS(trim(sanitize_text_field($_POST['mobile_no'])), trim(sanitize_text_field($_POST['message'])));
				if (strpos($Msg,"_")===false)
					$MsgType = "error";
			}
		}
	?>
	<div class="wrap">
		<h1><?php _e('Send SMS using 123eworld API',"123eworld");?></h1>
		<?php if ($Msg != "") {?><div class="<?php echo $MsgType =="success"?"updated":"error"; ?>"><p><?php echo $Msg;?></p></div><?php } ?>
		<form action="" method="post" id="form_send_sms">
		<?php wp_nonce_field( '123eworld_send_sms' );?>
			<p class="description">
				<label for="mobile-no"><?php _e('Mobile Number',"123eworld");?><br>
				<input type="text" id="mobile_no" name="mobile_no" class="large-text" size="15" placeholder="<?php _e('10 digit mobile number',"123eworld");?>" value="<?php echo isset($_POST['mobile_no'])?esc_attr($_POST['mobile_no']):""; ?>">
				</label>
			</p>
			<p class="description">
				<label for="mobile-no"><?php _e('Message',"123eworld");?><br>
				<textarea id="message" name="message" class="large-text" rows="3" placeholder="<?php _e('160 characters will be considered as 1 SMS.',"123eworld");?>"><?php echo isset($_POST['message'])?esc_attr($_POST['message']):""; ?></textarea>
				</label>
			</p>			
			<p class="submit">
				<input type="submit" name="send_sms" class="button-primary" value="<?php _e('Send SMS',"123eworld");?>"/>
			</p>
		</form>
		<?php $this->showHelp();?>
	</div>
	<?php
	}
    function sendSMS($MobileNo, $Message, $Print=0)
	{
		$MobileNo = str_replace(" ","",$MobileNo);
		$MobileNo = str_replace("+","",$MobileNo);
		$Message = trim($Message);
		if (strlen($MobileNo) < 10)
		{
			return __("Mobile Number is required","123eworld");
		}
		if (strlen($Message) < 1)
		{
			return __("Message is required","123eworld");
		}		
		$BaseUrl = 'http://www.smsjust.com/sms/user/urlsms.php?';
		$Url = $BaseUrl.'username='.$this->options['user_name'].'&pass='.$this->options['password'].'&senderid='.$this->options['senderid'].'&dest_mobileno='.$MobileNo.'&message='.urlencode($Message).'&response=Y';
		$Result = wp_remote_get($Url);
		$Return = is_array($Result) && isset($Result['body'])?$Result['body']:"";
		if ($Print)
			echo $Return;
		else
			return $Return;
	}
	
	function sendAjaxSMS()
	{
		$MobileNo = sanitize_text_field($_REQUEST['mobile_no']);
		$Message = sanitize_text_field($_REQUEST['message']);
		echo $this->sendSMS($MobileNo, $Message);
		exit();
	}
	function showHelp()
	{
	?>
		<h2>How to send SMS from programming?</h2>
		<p>
		1. You can call following method in your code to send SMS<br>
		<code>$SMSSendStatus = $EworldSMS->sendSMS($MobileNo,$Message);</code>
		</p>
		<p>
		2. You can call action hook in your code to send SMS<br>
		<code>do_action('eworld_send_sms', $MobileNo, $Message, $PrintTrueFalse);<br>
		$PrintTrueFalse - If 1 it will print result, If 0 then it will return result. Default 0 
		</code>
		</p>		
		<p>
		3. You can send sms by AJAX call.<br>
		Ajax action : <strong>send_sms</strong> <br>
		Data to send : a) mobile_no b) message<br><br>
		<code>
		var AjaxUrl = ajax_object.ajax_url;<br>
		var formData = {'action':'eworld_send_sms'};<br>
		formData['mobile_no'] = '9823372069';<br>
		formData['message'] = "Sending SMS using 123eworld SMS API";<br>
		jQuery.ajax({<br>
			type: "post",<br>
			dataType: "json",<br>
			url: AjaxUrl,<br>
			data: formData,<br>
			success: function(msg){<br>
			console.log(msg);<br>
			},<br>
			error:function(xhr, status, error){<br>
				//var err = eval("(" + xhr.responseText + ")");<br>
				console.log( xhr.responseText);<br>
				console.log(xhr);<br>
			}<br>
		});
		</code>
		
		</p>
	<?php
	}
}
//if( is_admin() )
$EworldSMS =  new EworldSMS();