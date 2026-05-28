/**
 * Lead Catalyst Calculators Frontend JS
 */

(function () {
    'use strict';

    // Industry formulas config matching the spreadsheet
    const INDUSTRIES = {
        manufacturing: {
            name: "Manufacturing",
            connectionRate: 0.06, // 6%
            leadRate: 0.20        // 20%
        },
        professional: {
            name: "Professional Services",
            connectionRate: 0.07, // 7%
            leadRate: 0.20        // 20%
        },
        it_managed: {
            name: "IT & Managed Services",
            connectionRate: 0.06, // 6%
            leadRate: 0.15        // 15%
        },
        facilities: {
            name: "Facilities Services",
            connectionRate: 0.08, // 8%
            leadRate: 0.22        // 22%
        },
        financial: {
            name: "Financial Services",
            connectionRate: 0.07, // 7%
            leadRate: 0.18        // 18%
        }
    };

    const CONSTANTS = {
        dialsWeekly: 150,
        dialsAnnual: 7500,
        fixedInvestment: 66000
    };

    // Helper: Formats currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            maximumFractionDigits: 0
        }).format(amount);
    }

    // Helper: Formats decimal numbers (e.g. connections or leads)
    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(num);
    }

    // Helper: Formats percentage
    function formatPercent(pct, includeSymbol = true) {
        const formatted = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 1
        }).format(pct);
        return includeSymbol ? `${formatted}%` : formatted;
    }

    // Main initialization function for a single calculator container
    function initCalculator(container) {
        if (!container || container.dataset.lcInitialized === 'true') {
            return;
        }

        const roiSection = container.querySelector('.lc-roi-calc-section');
        const missedSection = container.querySelector('.lc-missed-calc-section');
        const tabsContainer = container.querySelector('.lc-calculator-tabs');
        const leadForm = container.querySelector('#lc-lead-capture-form');

        // Handles tab switching if toggle mode is enabled (guarded)
        if (tabsContainer && tabsContainer.dataset.lcBound !== 'true') {
            const tabs = tabsContainer.querySelectorAll('.lc-tab-btn');
            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    const target = tab.dataset.target;
                    if (target === 'roi') {
                        roiSection.classList.remove('lc-hidden');
                        missedSection.classList.add('lc-hidden');
                        calculateROI(container);
                    } else {
                        roiSection.classList.add('lc-hidden');
                        missedSection.classList.remove('lc-hidden');
                        calculateMissed(container);
                    }
                });
            });
            tabsContainer.dataset.lcBound = 'true';
        }

        // Initialize ROI inputs and event listeners (guarded)
        if (roiSection) {
            const industrySelect = roiSection.querySelector('.lc-industry-select');
            const roiConvInput = roiSection.querySelector('.lc-roi-conv-input');
            const roiSaleInput = roiSection.querySelector('.lc-roi-sale-input');

            const updateRoi = () => calculateROI(container);

            if (industrySelect && industrySelect.dataset.lcBound !== 'true') {
                industrySelect.addEventListener('change', updateRoi);
                industrySelect.dataset.lcBound = 'true';
            }
            if (roiConvInput && roiConvInput.dataset.lcBound !== 'true') {
                roiConvInput.addEventListener('input', updateRoi);
                roiConvInput.dataset.lcBound = 'true';
            }
            if (roiSaleInput && roiSaleInput.dataset.lcBound !== 'true') {
                roiSaleInput.addEventListener('input', updateRoi);
                roiSaleInput.dataset.lcBound = 'true';
            }
        }

        // Initialize Missed Opportunity inputs and event listeners (guarded)
        if (missedSection) {
            const missedConvInput = missedSection.querySelector('.lc-missed-conv-input');
            const missedSaleInput = missedSection.querySelector('.lc-missed-sale-input');

            const updateMissed = () => calculateMissed(container);

            if (missedConvInput && missedConvInput.dataset.lcBound !== 'true') {
                missedConvInput.addEventListener('input', updateMissed);
                missedConvInput.dataset.lcBound = 'true';
            }
            if (missedSaleInput && missedSaleInput.dataset.lcBound !== 'true') {
                missedSaleInput.addEventListener('input', updateMissed);
                missedSaleInput.dataset.lcBound = 'true';
            }
        }

        // Lead capture form submit listener (guarded)
        if (leadForm && leadForm.dataset.lcSubmitBound !== 'true') {
            leadForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitLeadData(container, leadForm);
            });
            leadForm.dataset.lcSubmitBound = 'true';
        }

        // Initial calculations
        calculateROI(container);
        calculateMissed(container);

        // Mark container as initialized
        container.dataset.lcInitialized = 'true';
    }

    // ROI Calculator calculation logic
    function calculateROI(container) {
        const roiSection = container.querySelector('.lc-roi-calc-section');
        if (!roiSection || roiSection.classList.contains('lc-hidden')) return;

        // Fetch inputs
        const industryKey = roiSection.querySelector('.lc-industry-select').value;
        const convPct = parseFloat(roiSection.querySelector('.lc-roi-conv-input').value) || 0;
        const saleAmount = parseFloat(roiSection.querySelector('.lc-roi-sale-input').value) || 0;

        const industry = INDUSTRIES[industryKey] || INDUSTRIES.manufacturing;

        // Perform calculation steps
        const dialsWeekly = CONSTANTS.dialsWeekly;
        const dialsAnnual = CONSTANTS.dialsAnnual;

        const connWeekly = dialsWeekly * industry.connectionRate;
        const connAnnual = connWeekly * 50;

        const leadsWeekly = connWeekly * industry.leadRate;
        const leadsAnnual = leadsWeekly * 50;

        const salesAnnual = leadsAnnual * (convPct / 100);
        const revenueAnnual = salesAnnual * saleAmount;
        
        const netProfit = revenueAnnual - CONSTANTS.fixedInvestment;
        const roi = (revenueAnnual - CONSTANTS.fixedInvestment) / CONSTANTS.fixedInvestment * 100;

        // Update DOM elements in ROI calculator
        updateCell(roiSection, 'roi-dials-weekly', formatNumber(dialsWeekly));
        updateCell(roiSection, 'roi-dials-annual', formatNumber(dialsAnnual));
        
        updateCell(roiSection, 'roi-conn-weekly', formatNumber(connWeekly));
        updateCell(roiSection, 'roi-conn-annual', formatNumber(connAnnual));
        
        updateCell(roiSection, 'roi-leads-weekly', formatNumber(leadsWeekly));
        updateCell(roiSection, 'roi-leads-annual', formatNumber(leadsAnnual));

        updateCell(roiSection, 'roi-sales-annual', formatNumber(salesAnnual));
        updateCell(roiSection, 'roi-rev-annual', formatCurrency(revenueAnnual));

        // Update hero display badge
        const heroVal = roiSection.querySelector('.lc-roi-hero-value');
        const heroBox = roiSection.querySelector('.lc-roi-hero');
        const heroSub = roiSection.querySelector('.lc-roi-hero-sub');

        if (heroVal) {
            heroVal.textContent = formatPercent(roi);
            // Apply color class based on positive/negative ROI
            if (roi >= 0) {
                heroVal.className = 'lc-roi-hero-value roi-val-positive';
                if (heroBox) {
                    heroBox.classList.add('roi-hero-positive');
                    heroBox.classList.remove('roi-hero-negative');
                }
            } else {
                heroVal.className = 'lc-roi-hero-value roi-val-negative';
                if (heroBox) {
                    heroBox.classList.add('roi-hero-negative');
                    heroBox.classList.remove('roi-hero-positive');
                }
            }
        }

        if (heroSub) {
            heroSub.textContent = `Net Annual Return: ${formatCurrency(netProfit)}`;
        }
    }

    // Missed Opportunity Calculator calculation logic
    function calculateMissed(container) {
        const missedSection = container.querySelector('.lc-missed-calc-section');
        if (!missedSection || missedSection.classList.contains('lc-hidden')) return;

        // Fetch inputs
        const convPct = parseFloat(missedSection.querySelector('.lc-missed-conv-input').value) || 0;
        const saleAmount = parseFloat(missedSection.querySelector('.lc-missed-sale-input').value) || 0;

        // Fixed parameters for Missed Opportunity (7% conn rate, 20% lead rate)
        const connRate = 0.07;
        const leadRate = 0.20;

        const dialsWeekly = CONSTANTS.dialsWeekly;
        const dialsAnnual = CONSTANTS.dialsAnnual;

        const connWeekly = dialsWeekly * connRate;
        const connAnnual = connWeekly * 50;

        const leadsWeekly = connWeekly * leadRate;
        const leadsAnnual = leadsWeekly * 50;

        const salesAnnual = leadsAnnual * (convPct / 100);
        const revenueAnnual = salesAnnual * saleAmount;

        // Update DOM elements in Missed Opportunity calculator
        updateCell(missedSection, 'missed-dials-weekly', formatNumber(dialsWeekly));
        updateCell(missedSection, 'missed-dials-annual', formatNumber(dialsAnnual));
        
        updateCell(missedSection, 'missed-conn-weekly', formatNumber(connWeekly));
        updateCell(missedSection, 'missed-conn-annual', formatNumber(connAnnual));
        
        updateCell(missedSection, 'missed-leads-weekly', formatNumber(leadsWeekly));
        updateCell(missedSection, 'missed-leads-annual', formatNumber(leadsAnnual));

        updateCell(missedSection, 'missed-sales-annual', formatNumber(salesAnnual));
        updateCell(missedSection, 'missed-rev-annual', formatCurrency(revenueAnnual));

        // Update hero display badge
        const heroVal = missedSection.querySelector('.lc-roi-hero-value');
        if (heroVal) {
            heroVal.textContent = formatCurrency(revenueAnnual);
        }
    }

    // Helper: Updates cell text content safely
    function updateCell(section, classSuffix, value) {
        const cell = section.querySelector(`.lc-val-${classSuffix}`);
        if (cell) {
            cell.textContent = value;
        }
    }

    // Submit Lead Capture Form Async to REST API
    function submitLeadData(container, form) {
        const messageBox = container.querySelector('.lc-form-message');
        const submitBtn = form.querySelector('.lc-submit-btn');
        const spinner = submitBtn.querySelector('.lc-btn-spinner');
        const btnText = submitBtn.querySelector('.lc-btn-text');

        // Check active calculator
        const isRoiActive = !container.querySelector('.lc-roi-calc-section').classList.contains('lc-hidden');
        let calcType = 'roi';
        let industry = '';
        let inputs = {};
        let outputs = {};

        if (isRoiActive) {
            calcType = 'roi';
            const roiSection = container.querySelector('.lc-roi-calc-section');
            industry = roiSection.querySelector('.lc-industry-select').value;
            
            inputs['conversion-rate'] = roiSection.querySelector('.lc-roi-conv-input').value + '%';
            inputs['average-sale'] = '$' + roiSection.querySelector('.lc-roi-sale-input').value;
            
            outputs['dials-annual'] = roiSection.querySelector('.lc-val-roi-dials-annual').textContent;
            outputs['connections-annual'] = roiSection.querySelector('.lc-val-roi-conn-annual').textContent;
            outputs['qualified-leads-annual'] = roiSection.querySelector('.lc-val-roi-leads-annual').textContent;
            outputs['annual-conversions'] = roiSection.querySelector('.lc-val-roi-sales-annual').textContent;
            outputs['annual-revenue'] = roiSection.querySelector('.lc-val-roi-rev-annual').textContent;
            outputs['estimated-roi'] = roiSection.querySelector('.lc-roi-hero-value').textContent;
            outputs['net-annual-return'] = roiSection.querySelector('.lc-roi-hero-sub').textContent.replace('Net Annual Return: ', '');
        } else {
            calcType = 'missed_opportunity';
            const missedSection = container.querySelector('.lc-missed-calc-section');
            
            inputs['conversion-rate'] = missedSection.querySelector('.lc-missed-conv-input').value + '%';
            inputs['average-sale'] = '$' + missedSection.querySelector('.lc-missed-sale-input').value;
            
            outputs['dials-annual'] = missedSection.querySelector('.lc-val-missed-dials-annual').textContent;
            outputs['connections-annual'] = missedSection.querySelector('.lc-val-missed-conn-annual').textContent;
            outputs['qualified-leads-annual'] = missedSection.querySelector('.lc-val-missed-leads-annual').textContent;
            outputs['annual-conversions'] = missedSection.querySelector('.lc-val-missed-sales-annual').textContent;
            outputs['missed-revenue-opportunity'] = missedSection.querySelector('.lc-roi-hero-value').textContent;
        }

        const payload = {
            name: form.querySelector('.lc-field-name').value,
            company: form.querySelector('.lc-field-company').value,
            email: form.querySelector('.lc-field-email').value,
            phone: form.querySelector('.lc-field-phone').value,
            calc_type: calcType,
            industry: industry,
            inputs: inputs,
            outputs: outputs
        };

        // UI Feedback: Loading
        submitBtn.disabled = true;
        if (spinner) spinner.classList.remove('lc-hidden');
        if (btnText) btnText.textContent = 'Submitting...';
        if (messageBox) {
            messageBox.className = 'lc-form-message lc-hidden';
        }

        // Fetch using localized WP vars
        fetch(lcVars.restUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': lcVars.nonce
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json().then(data => ({ status: response.status, data })))
        .then(({ status, data }) => {
            if (status === 200 && data.success) {
                // Success
                if (messageBox) {
                    messageBox.textContent = data.message;
                    messageBox.className = 'lc-form-message';
                    messageBox.style.backgroundColor = '#ecfdf5';
                    messageBox.style.border = '1px solid #10b981';
                    messageBox.style.color = '#065f46';
                }
                form.reset();
            } else {
                // Fail
                throw new Error(data.message || 'Submission failed');
            }
        })
        .catch(err => {
            if (messageBox) {
                messageBox.textContent = err.message || 'An error occurred. Please try again.';
                messageBox.className = 'lc-form-message';
                messageBox.style.backgroundColor = '#fef2f2';
                messageBox.style.border = '1px solid #ef4444';
                messageBox.style.color = '#991b1b';
            }
        })
        .finally(() => {
            // UI Feedback: Reset
            submitBtn.disabled = false;
            if (spinner) spinner.classList.add('lc-hidden');
            if (btnText) btnText.textContent = 'Send My Results';
        });
    }

    // DOM Ready hook for non-Elementor rendering
    document.addEventListener('DOMContentLoaded', function () {
        const containers = document.querySelectorAll('.lc-calculator-container');
        containers.forEach(container => {
            initCalculator(container);
        });
    });

    // Elementor Preview / Active Editor hook
    jQuery(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/lead-catalyst_calculator.default', function ($scope) {
            // Find and initialize the calculator in the Elementor container scope
            const container = $scope[0].querySelector('.lc-calculator-container');
            if (container) {
                // Only force re-initialization inside the active Elementor Editor
                if (typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode && elementorFrontend.isEditMode()) {
                    delete container.dataset.lcInitialized;
                    // Reset inputs binding flags in editor to allow re-binding
                    const boundElements = container.querySelectorAll('[data-lc-bound]');
                    boundElements.forEach(el => delete el.dataset.lcBound);
                    if (container.querySelector('#lc-lead-capture-form')) {
                        delete container.querySelector('#lc-lead-capture-form').dataset.lcSubmitBound;
                    }
                }
                initCalculator(container);
            }
        });
    });

})();
