<?php
/**
 * Custom Elementor Widget for ROI & Missed Opportunity Calculators
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Lead_Catalyst_Calculator_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     */
    public function get_name() {
        return 'lead_catalyst_calculator';
    }

    /**
     * Get widget title.
     */
    public function get_title() {
        return esc_html__( 'Lead Catalyst Calculator', 'lead-catalyst' );
    }

    /**
     * Get widget icon.
     */
    public function get_icon() {
        return 'eicon-calculator';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() {
        return [ 'general' ];
    }

    /**
     * Get script dependencies.
     */
    public function get_script_depends() {
        return [ 'lead-catalyst-calculator-script' ];
    }

    /**
     * Get style dependencies.
     */
    public function get_style_depends() {
        return [ 'lead-catalyst-calculator-style' ];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {

        // Content Tab: Settings Section
        $this->start_controls_section(
            'section_settings',
            [
                'label' => esc_html__( 'Calculator Settings', 'lead-catalyst' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'calc_mode',
            [
                'label'   => esc_html__( 'Calculator Mode', 'lead-catalyst' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'toggle',
                'options' => [
                    'toggle'             => esc_html__( 'Tabbed/Toggle View (Both)', 'lead-catalyst' ),
                    'roi'                => esc_html__( 'ROI Calculator Only', 'lead-catalyst' ),
                    'missed_opportunity' => esc_html__( 'Missed Opportunity Calculator Only', 'lead-catalyst' ),
                ],
            ]
        );

        $this->add_control(
            'default_tab',
            [
                'label'     => esc_html__( 'Default Active Tab', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => 'roi',
                'options'   => [
                    'roi'                => esc_html__( 'ROI Calculator', 'lead-catalyst' ),
                    'missed_opportunity' => esc_html__( 'Missed Opportunity', 'lead-catalyst' ),
                ],
                'condition' => [
                    'calc_mode' => 'toggle',
                ],
            ]
        );

        $this->add_control(
            'roi_title',
            [
                'label'     => esc_html__( 'ROI Form Title', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => esc_html__( 'Estimate Your ROI', 'lead-catalyst' ),
                'placeholder' => esc_html__( 'Type title here', 'lead-catalyst' ),
            ]
        );

        $this->add_control(
            'missed_title',
            [
                'label'     => esc_html__( 'Missed Opportunity Form Title', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => esc_html__( 'Calculate Missed Opportunities', 'lead-catalyst' ),
                'placeholder' => esc_html__( 'Type title here', 'lead-catalyst' ),
            ]
        );

        $this->add_control(
            'default_conv_rate',
            [
                'label'   => esc_html__( 'Default Conversion Rate (%)', 'lead-catalyst' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'min'     => 0,
                'max'     => 100,
                'step'    => 0.1,
                'default' => 10,
            ]
        );

        $this->add_control(
            'default_sale_amount',
            [
                'label'   => esc_html__( 'Default Average Sale ($)', 'lead-catalyst' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'min'     => 0,
                'step'    => 1,
                'default' => 5000,
            ]
        );

        $this->end_controls_section();

        // Style Tab: General Layout & Card Section
        $this->start_controls_section(
            'section_style_general',
            [
                'label' => esc_html__( 'General & Layout', 'lead-catalyst' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_max_width',
            [
                'label'      => esc_html__( 'Max Width', 'lead-catalyst' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'range'      => [
                    'px' => [
                        'min' => 400,
                        'max' => 1600,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 1000,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .lc-calculator-container' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_bg',
            [
                'label'     => esc_html__( 'Card Background Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'lead-catalyst' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors'  => [
                    '{{WRAPPER}} .lc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .lc-card',
            ]
        );

        $this->end_controls_section();

        // Style Tab: Typography & Color Section
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => esc_html__( 'Typography & Colors', 'lead-catalyst' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => esc_html__( 'Heading Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-form-title, {{WRAPPER}} .lc-results-title' => 'color: {{VALUE}}; border-left-color: {{VALUE}};',
                    '{{WRAPPER}} .lc-tab-btn.active' => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .lc-form-title, {{WRAPPER}} .lc-results-title, {{WRAPPER}} .lc-tab-btn',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label'     => esc_html__( 'Labels Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-label, {{WRAPPER}} .lc-col-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'label_typography',
                'selector' => '{{WRAPPER}} .lc-label, {{WRAPPER}} .lc-col-label',
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label'     => esc_html__( 'Values Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-col-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'value_typography',
                'selector' => '{{WRAPPER}} .lc-col-value',
            ]
        );

        $this->end_controls_section();

        // Style Tab: Inputs Styling
        $this->start_controls_section(
            'section_style_inputs',
            [
                'label' => esc_html__( 'Input Fields', 'lead-catalyst' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'input_bg',
            [
                'label'     => esc_html__( 'Input Background', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-input, {{WRAPPER}} .lc-select' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_text_color',
            [
                'label'     => esc_html__( 'Input Text Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-input, {{WRAPPER}} .lc-select' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_border_color',
            [
                'label'     => esc_html__( 'Input Border Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-input, {{WRAPPER}} .lc-select' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_focus_border_color',
            [
                'label'     => esc_html__( 'Input Focus Border Color', 'lead-catalyst' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .lc-input:focus, {{WRAPPER}} .lc-select:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 3px {{VALUE}}26;',
                ],
            ]
        );

        $this->add_control(
            'input_border_radius',
            [
                'label'      => esc_html__( 'Input Border Radius', 'lead-catalyst' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors'  => [
                    '{{WRAPPER}} .lc-input, {{WRAPPER}} .lc-select' => 'border-radius: {{TOP}}{{SIZE}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $calc_mode = $settings['calc_mode'];
        $default_tab = $settings['default_tab'];
        $roi_title = $settings['roi_title'];
        $missed_title = $settings['missed_title'];
        $default_conv = $settings['default_conv_rate'];
        $default_sale = $settings['default_sale_amount'];

        // Determine initial visibility classes based on settings
        $show_roi = true;
        $show_missed = true;
        
        if ( $calc_mode === 'toggle' ) {
            if ( $default_tab === 'roi' ) {
                $show_missed = false;
            } else {
                $show_roi = false;
            }
        } elseif ( $calc_mode === 'roi' ) {
            $show_missed = false;
        } elseif ( $calc_mode === 'missed_opportunity' ) {
            $show_roi = false;
        }
        ?>
        <div class="lc-calculator-container" data-calc-mode="<?php echo esc_attr( $calc_mode ); ?>">
            
            <?php if ( $calc_mode === 'toggle' ) : ?>
                <div class="lc-calculator-tabs">
                    <button class="lc-tab-btn <?php echo ( $default_tab === 'roi' ) ? 'active' : ''; ?>" data-target="roi">
                        <?php esc_html_e( 'ROI Calculator', 'lead-catalyst' ); ?>
                    </button>
                    <button class="lc-tab-btn <?php echo ( $default_tab === 'missed_opportunity' ) ? 'active' : ''; ?>" data-target="missed">
                        <?php esc_html_e( 'Missed Opportunity', 'lead-catalyst' ); ?>
                    </button>
                </div>
            <?php endif; ?>

            <!-- ROI CALCULATOR SECTION -->
            <div class="lc-roi-calc-section <?php echo ( $show_roi ) ? '' : 'lc-hidden'; ?>">
                <div class="lc-calculator-grid">
                    <!-- Left Panel: Inputs -->
                    <div class="lc-card">
                        <h3 class="lc-form-title"><?php echo esc_html( $roi_title ); ?></h3>
                        
                        <div class="lc-form-group">
                            <label class="lc-label" for="roi-industry"><?php esc_html_e( 'Select Industry Type', 'lead-catalyst' ); ?></label>
                            <select id="roi-industry" class="lc-select lc-industry-select">
                                <option value="manufacturing" selected><?php esc_html_e( 'Manufacturing', 'lead-catalyst' ); ?></option>
                                <option value="professional"><?php esc_html_e( 'Professional Services', 'lead-catalyst' ); ?></option>
                                <option value="it_managed"><?php esc_html_e( 'IT & Managed Services', 'lead-catalyst' ); ?></option>
                                <option value="facilities"><?php esc_html_e( 'Facilities Services', 'lead-catalyst' ); ?></option>
                                <option value="financial"><?php esc_html_e( 'Financial Services', 'lead-catalyst' ); ?></option>
                            </select>
                        </div>

                        <div class="lc-form-group">
                            <label class="lc-label" for="roi-conv-rate"><?php esc_html_e( 'Conversion to Sale (%)', 'lead-catalyst' ); ?></label>
                            <div class="lc-input-wrapper">
                                <input type="number" id="roi-conv-rate" class="lc-input lc-roi-conv-input" 
                                       min="0" max="100" step="0.1" value="<?php echo esc_attr( $default_conv ); ?>">
                                <span class="lc-input-icon" style="left: auto; right: 14px;">%</span>
                            </div>
                        </div>

                        <div class="lc-form-group">
                            <label class="lc-label" for="roi-sale-amount"><?php esc_html_e( 'Average Sale Amount ($)', 'lead-catalyst' ); ?></label>
                            <div class="lc-input-wrapper">
                                <span class="lc-input-icon">$</span>
                                <input type="number" id="roi-sale-amount" class="lc-input lc-roi-sale-input" 
                                       min="0" step="1" value="<?php echo esc_attr( $default_sale ); ?>" style="padding-left: 30px;">
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Results -->
                    <div class="lc-card lc-results-panel">
                        <h3 class="lc-results-title"><?php esc_html_e( 'Expected Results', 'lead-catalyst' ); ?></h3>
                        
                        <table class="lc-results-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Metric', 'lead-catalyst' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Weekly', 'lead-catalyst' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Annual', 'lead-catalyst' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Dials Made', 'lead-catalyst' ); ?></td>
                                    <td class="lc-col-value lc-val-roi-dials-weekly">0</td>
                                    <td class="lc-col-value lc-val-roi-dials-annual">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Connections', 'lead-catalyst' ); ?></td>
                                    <td class="lc-col-value lc-val-roi-conn-weekly">0</td>
                                    <td class="lc-col-value lc-val-roi-conn-annual">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Qualified Leads', 'lead-catalyst' ); ?></td>
                                    <td class="lc-col-value lc-val-roi-leads-weekly">0</td>
                                    <td class="lc-col-value lc-val-roi-leads-annual">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Annual Conversions (Sales)', 'lead-catalyst' ); ?></td>
                                    <td></td>
                                    <td class="lc-col-value lc-val-roi-sales-annual" style="font-weight: 700;">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Annual Revenue (Value)', 'lead-catalyst' ); ?></td>
                                    <td></td>
                                    <td class="lc-col-value lc-val-roi-rev-annual" style="font-weight: 700; font-size: 16px;">$0</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="lc-roi-hero lc-animate-pulse">
                            <div class="lc-roi-hero-label"><?php esc_html_e( 'Estimated ROI', 'lead-catalyst' ); ?></div>
                            <div class="lc-roi-hero-value">0%</div>
                            <div class="lc-roi-hero-sub">Net Annual Return: $0</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MISSED OPPORTUNITY CALCULATOR SECTION -->
            <div class="lc-missed-calc-section <?php echo ( $show_missed ) ? '' : 'lc-hidden'; ?>">
                <div class="lc-calculator-grid">
                    <!-- Left Panel: Inputs -->
                    <div class="lc-card">
                        <h3 class="lc-form-title"><?php echo esc_html( $missed_title ); ?></h3>
                        
                        <div class="lc-form-group">
                            <label class="lc-label" for="missed-conv-rate"><?php esc_html_e( 'Conversion to Sale (%)', 'lead-catalyst' ); ?></label>
                            <div class="lc-input-wrapper">
                                <input type="number" id="missed-conv-rate" class="lc-input lc-missed-conv-input" 
                                       min="0" max="100" step="0.1" value="<?php echo esc_attr( $default_conv ); ?>">
                                <span class="lc-input-icon" style="left: auto; right: 14px;">%</span>
                            </div>
                        </div>

                        <div class="lc-form-group">
                            <label class="lc-label" for="missed-sale-amount"><?php esc_html_e( 'Average Sale Amount ($)', 'lead-catalyst' ); ?></label>
                            <div class="lc-input-wrapper">
                                <span class="lc-input-icon">$</span>
                                <input type="number" id="missed-sale-amount" class="lc-input lc-missed-sale-input" 
                                       min="0" step="1" value="<?php echo esc_attr( $default_sale ); ?>" style="padding-left: 30px;">
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Results -->
                    <div class="lc-card lc-results-panel lc-missed-hero">
                        <h3 class="lc-results-title"><?php esc_html_e( 'Expected Results', 'lead-catalyst' ); ?></h3>
                        
                        <table class="lc-results-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Metric', 'lead-catalyst' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Weekly', 'lead-catalyst' ); ?></th>
                                    <th style="text-align: right;"><?php esc_html_e( 'Annual', 'lead-catalyst' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Dials Made', 'lead-catalyst' ); ?></td>
                                    <td class="lc-col-value lc-val-missed-dials-weekly">0</td>
                                    <td class="lc-col-value lc-val-missed-dials-annual">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Connections', 'lead-catalyst' ); ?></td>
                                    <td class="lc-col-value lc-val-missed-conn-weekly">0</td>
                                    <td class="lc-col-value lc-val-missed-conn-annual">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Qualified Leads', 'lead-catalyst' ); ?></td>
                                    <td class="lc-col-value lc-val-missed-leads-weekly">0</td>
                                    <td class="lc-col-value lc-val-missed-leads-annual">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Annual Conversions (Sales)', 'lead-catalyst' ); ?></td>
                                    <td></td>
                                    <td class="lc-col-value lc-val-missed-sales-annual" style="font-weight: 700;">0</td>
                                </tr>
                                <tr>
                                    <td class="lc-col-label"><?php esc_html_e( 'Annual Revenue (Value)', 'lead-catalyst' ); ?></td>
                                    <td></td>
                                    <td class="lc-col-value lc-val-missed-rev-annual" style="font-weight: 700; font-size: 16px;">$0</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="lc-roi-hero lc-animate-pulse">
                            <div class="lc-roi-hero-label"><?php esc_html_e( 'Missed Revenue Opportunity', 'lead-catalyst' ); ?></div>
                            <div class="lc-roi-hero-value">$0</div>
                            <div class="lc-roi-hero-sub"><?php esc_html_e( 'Annual Cost of Doing Nothing', 'lead-catalyst' ); ?></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}
