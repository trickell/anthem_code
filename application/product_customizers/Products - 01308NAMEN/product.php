<?php

/**
 * Customized Product - 01308NAMEN
 * Product Customizer
 *
 * @author JMadrigal
 */
 
class CustomProduct extends CustomProductTemplate {
	
    //======================================================================
    // Configure the Primary Form
    //======================================================================
    public function configureForm() {
        $this->template->add_css('css/proposals.css');
        $this->formidable->setFormAttribute('id', '01308NAMEN_form');
		$this->formidable->setFormValidate(TRUE);
        //$this->formidable->setWizardLastButtonLabel('Finish');

        $this->manageIndesign();
        $this->setupProductStep1();

        $this->formidable->setValues($this->original_values);

    }

	
    //----------------------------------------------------------------------

    function __construct() {
        // Constructs all the variables needed for setupProductStep1
        // Array Constructions
        $h1 = Array();
        $h2 = Array();
        $h3 = Array();
        $h4 = Array();
        $h5 = Array();

        $p1 = Array();
        $p2 = Array();
        $p3 = Array();
        $p4 = Array();
        $p5 = Array();
        $p6 = Array();
        $p7 = Array();
        $p8 = Array();
        $p9 = Array();
        $p10 = Array();
        $p11 = Array();

    }

    // Customizable online tool flier
    private function setupProductStep1() {

        // $page = $this->formidable->addWizardPage('Basic Information', array('id'=>'last_page'));
        $page = $this->formidable;

        $page->addNote('Select the options below that you would like to include in your customizable flyer. Click Create to generate your customized product.');

        // First Fieldset for Health Options
        // ---------------------------------
        // JMad - Each one of these "checkbox" options are actually considered blocks of information being
        // pulled from hidden / hidden-text fields. When an option is checked, it checks the data then in
        // the following text variables.
        // ----------------------------------
        // What I did to fixe this is I output the checkbox as part of the Fieldset, then the rest of the
        // values needed for the checkbox are written out as an Array of values to a variable.
        // Ex. -- $h1 (this being the checkbox) ['t2'] --> This being the textbox value it's attached to

        $page->add('health_options','Health & Wellness', 'checkbox', 'checked', null, array('no_label' => TRUE) );
        $fs1 = $page->addFieldSet('', null, 'health_options');

        $fs1->add('hstep1','MyHealth Assessment','checkbox',''); #Text_3
            $h1['t4'] = "Your first step toward a healthier lifestyle";
            $h1['t5'] = "Gain personal insights into your current health, your health risks, and what you can do to enjoy a healthier life. You complete a confidential assessment of your health and health care status, then receive a health assessment score and risk profile based on your specific answers. You also get tips and action options to help you improve your health.";
            $h1['t6'] = "To use MyHealth Assessment:";
            $h1['t7'] = "Log in at bcbsga.com. Click on \"Health &amp; Wellness\"";

        $fs1->add('hstep2','MyHealth Record','checkbox',''); #Text_8
            $h2['t9'] = "Your health history in one secure location";
            $h2['t10'] = "Keep your medical records organized, secure and easily accessible for emergencies and everyday use. Enter your information such as dates of immunizations, tests and screenings, prescription and over-the-counter drugs you take, medical conditions, and more. Print and share with your doctors to help avoid potential drug interactions and duplicative tests and procedures.";
            $h2['t11'] = "To use MyHealth Record:";
            $h2['t12'] = "Log in at bcbsga.com. Click on \"Health &amp; Wellness\"";

        $fs1->add('hstep3','SpecialOffers','checkbox',''); #Text_13
            $h3['t14'] = "Discounts on health-related products and services";
            $h3['t15'] = "Enjoy members-only discounts on vitamins, health and beauty products, chiropractic care, acupuncture, massage therapy, LASIK eye surgery, eyeglass frames and contact lenses, hearing aids and audiology services, fitness center memberships, weight-loss programs and more.";
            $h3['t16'] = "To access all discounts:";
            $h3['t17'] = "Log in at bcbsga.com. Click on \"Health &amp; Wellness\"";

        $fs1->add('hstep4','Zegat&reg; Health Survey','checkbox',''); #Text_18
            $h4['t19'] = "Doctor recommendations from your peers";
            $h4['t20'] = "Benefit from the experiences of fellow Blue Cross Blue Shield of Georgia members to help you find the doctor that’s right for you. We’ve teamed with Zagat Survey, the world’s most trusted source of recommendations by consumers, for consumers, to let you rate your doctors and see what others say about them.";
            $h4['t21'] = "To access the Zagat Health Survey:";
            $h4['t22'] = "Log in at bcbsga.com. Go to your Account Summary page to rate your Recently Visited Providers.";

        $fs1->add('hstep5','Healthy Lifestyles','checkbox',''); #Text_23
            $h5['t24'] = "Support to help you achieve your goals";
            $h5['t25'] = "Lose weight, stop smoking, stress less and exercise more with our online tools and resources. Take advantage of online fitness tracking and customized workout plans, discounts on spa services and massage therapists, healthy recipes, smoking cessation programs and more. Plus, get the support you need at our online community forums.";
            $h5['t26'] = "To learn more:";
            $h5['t27'] = "Log in at bcbsga.com. Click on \"Health &amp; Wellness\"";

        // Second Fieldset for Plans and Benefits
        $page->add('plan_options','Plans & Benifits','checkbox', '', null, array('no_label' => TRUE) );
        $fs2 = $page->addFieldSet('', null, 'plan_options');

        $fs2->add('pstep1','Anthem Care Comparison','checkbox','');
            $p1['t31'] = "Quality and cost information at your fingertips";
            $p1['t32'] = "Make informed decisions and save money by comparing actual costs for common procedures at hospitals and facilities in your area. In addition to price information, you can see procedure and quality comparisons that gauge performance and safety at each facility.";
            $p1['t33'] = "To use Anthem Care Comparison:";
            $p1['t34'] = "Log in at bcbsga.com. Click on \"Plans &amp; Benefits\"";

        $fs2->add('pstep2','Coverage Advisor','checkbox','');
            $p2['t36'] = "A customized comparison of your health care needs and costs";
            $p2['t37'] = "You have a wide range of Blue Cross Blue Shield of Georgia health plans to choose from; Coverage Advisor helps you choose the right one for you and your family. It helps you forecast your health care needs and costs and provides you with a clear comparison of benefit plans. If you have a medical savings account, it can also recommend contribution amounts to help cover expenses.";
            $p2['t38'] = "To use Coverage Advisor:";
            $p2['t39'] = "Log in at bcbsga.com. Click on \"Plans &amp; Benefits\"";

        $fs2->add('pstep3','Claims Look-up','checkbox','');
            $p3['t46'] = "Easy access to claims information";
            $p3['t47'] = "Stay on top of your medical claims with this easy online view. You can see the amounts charged to your medical savings account, the amounts paid by your traditional health coverage, or the amounts for which you’re responsible. You may also choose to receive an email when a claim has been processed instead of receiving notification by mail.";
            $p3['t48'] = "To look up a claim:";
            $p3['t49'] = "Log in at bcbsga.com. Click on \"Plans &amp; Benefits\"";

        $fs2->add('pstep4','Flexible Spending Account','checkbox','');
            $p4['t51'] = "An overview of your spending and savings";
            $p4['t52'] = "See your contributions and reimbursements, access reimbursement request forms, learn about your plan and more, all in one convenient place.";
            $p4['t53'] = "To access your Flexible Spending Account:";
            $p4['t54'] = "Log in at bcbsga.com. Click on \"Plans &amp; Benefits\"";

        $fs2->add('pstep5','Online Provider Finder','checkbox','');
            $p5['t56'] = "The quick and easy way to find your doctor";
            $p5['t57'] = "Search for doctors, hospitals and other health care facilities quickly online. You can make your search more specific by choosing a specialty or entering the name of a doctor or facility. If you’re away from home, you can also search our National Directory.";
            $p5['t58'] = "To search our Online Provider Finder:";
            $p5['t59'] = "Log in at bcbsga.com. Select \"Find a Doctor\" and simply follow the steps outlined on the screen";

        $fs2->add('pstep6','Account Summary Page','checkbox','');
            $p5['t61'] = "Your personal gateway to information and resources";
            $p5['t62'] = "See an overview of your benefits, doctors, prescriptions and more. Just click to learn more or perform common tasks such as refilling a prescription or checking on claims. You can customize your Account Summary Page so it looks and works exactly the way you want it to.";
            $p5['t63'] = "To go to your Account Summary Page:";
            $p5['t64'] = "Log in at bcbsga.com. Click on \"Plans &amp; Benefits\"";

        $fs2->add('pstep7','HIA Online Health Site','checkbox','');

        $fs2->add('pstep8','HSA Online Health Site','checkbox','');
        $fs2->add('pstep9','HRA Online Health Site','checkbox','');
        $fs2->add('pstep10','Blue Precision Program','checkbox','');
        $fs2->add('pstep11','Designated Blue Distinction Centers','checkbox','');


    }

	//----------------------------------------------------------------------
	
	/**
	 *
	 */
	public function setValues() {

                // Need to know what options to set this up with

		$map = array(
                    
                    'Text_1' => 'health_options',
                    'Text_2' => 'Now it’s easier than ever to improve your health and well-being. Simply log in at bcbsga.com. You have access to an array of innovative tools to help you manage your health and achieve your goals.',
                    // Health Options
			'Text_3' => 'hstep1',
			'Text_8' => 'hstep2',
			'Text_13' => 'hstep3',
                        'Text_18' => 'hstep4',
                        'Text_23' => 'hstep5',

                    'Text_28' => 'plan_options',
                    'Text_29' => 'Bcbsga.com makes complex information easy to understand and easy to use. That makes it easier to make the right decisions for you and your family.',
                    // Plans and Benefits
                        'Text_30' => 'pstep1',
                        'Text_35' => 'pstep2',
                        'Text_45' => 'pstep3',
                        'Text_50' => 'pstep4',
                        'Text_55' => 'pstep5',
                        'Text_60' => 'pstep6',
                        'Text_65' => 'pstep7',
                        'Text_80' => 'pstep8',
                        'Text_85' => 'pstep9',
                        'Text_70' => 'pstep10',
                        'Text_75' => 'pstep11',
		);
		$this->productcustomizer->map($this->formidable->values, $map);
		
	}
}