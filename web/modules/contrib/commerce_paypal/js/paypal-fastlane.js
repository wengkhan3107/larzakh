((Drupal, once) => {
  /**
   * The Drupal Commerce Fastlane Element Instances.
   *
   * Only one instance is supported.
   *
   * @type {Map}
   */
  Drupal.CommerceFastlaneInstances = new Map();

  class CommercePaypalFastlane {
    /**
     * Whether the component has been initialized.
     */
    initialized;

    /**
     * The settings.
     */
    settings;

    /**
     * The form.
     */
    form;

    /**
     * The active checkout pane id.
     */
    activeCheckoutPaneId;

    /**
     * The visited checkout pane ids.
     */
    visitedPaneIds;

    /**
     * The email.
     */
    email;

    /**
     * Whether the member is authenticated successfully.
     */
    memberAuthenticatedSuccessfully;

    /**
     * The overlay modal.
     */
    overlayModal;

    /**
     * Whether the form can be submitted.
     */
    allowSubmit;

    /**
     * The shipping address.
     */
    shippingAddress;

    /**
     * The profile.
     */
    profile;

    /**
     * The profile data.
     */
    profileData;

    /**
     * The payment token.
     */
    paymentToken;

    /**
     * The payment component.
     */
    paymentComponent;

    /**
     * The identity.
     */
    identity;

    /**
     * The Fastlane payment component.
     */
    FastlanePaymentComponent;

    /**
     * The Commerce PayPal Fastlane constructor.
     */
    constructor() {
      if (!this.initialized) {
        this.initialized = true;
        this.settings = null;
        this.form = null;
        this.activeCheckoutPaneId = null;
        this.visitedPaneIds = [];
        this.email = null;
        this.memberAuthenticatedSuccessfully = false;
        this.overlayModal = null;
        this.allowSubmit = false;
        this.shippingAddress = null;
        this.profile = null;
        this.profileData = null;
        this.paymentToken = null;
        this.paymentComponent = null;
        this.identity = null;
        this.FastlanePaymentComponent = null;
      }
    }

    /**
     * Initialize the fastlane component.
     *
     * @param {Object} settings
     *   The settings.
     */
    async initializeFastlane(settings) {
      // Ensure we initialize the script only once.
      const script = document.createElement('script');
      script.src = settings.src;
      script.type = 'text/javascript';
      script.setAttribute(
        'data-partner-attribution-id',
        'CommerceGuys_Cart_FL',
      );
      script.setAttribute('data-sdk-client-token', settings.clientToken);
      document.getElementsByTagName('head')[0].appendChild(script);

      async function waitForSdk() {
        while (typeof window.paypal === 'undefined') {
          /* eslint-disable-next-line no-await-in-loop */
          await new Promise((resolve) => {
            setTimeout(resolve, 100);
          });
        }
        return window.paypal;
      }
      await waitForSdk();
      await this.initializeCheckout(settings);
    }

    /**
     * Populates the shipping profile form.
     *
     * @param {*} shippingAddress
     *   The shipping address.
     * @return {Promise<boolean>}
     *   Whether the profile form was populated.
     */
    async populateShippingInformationProfileForm(shippingAddress) {
      if (
        shippingAddress?.address?.countryCode &&
        this.form?.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-address-line1"]',
        )
      ) {
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-given-name"]',
        ).value = shippingAddress?.name?.firstName ?? '';
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-family-name"]',
        ).value = shippingAddress?.name?.lastName ?? '';

        this.form.querySelector(
          'select[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-country-code"]',
        ).value = shippingAddress?.address?.countryCode ?? '';
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-postal-code"]',
        ).value = shippingAddress?.address?.postalCode ?? '';
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-administrative-area"]',
        ).value = shippingAddress?.address?.adminArea1 ?? '';
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-locality"]',
        ).value = shippingAddress?.address?.adminArea2 ?? '';
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-address-line1"]',
        ).value = shippingAddress?.address?.addressLine1 ?? '';
        this.form.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipping-profile-address-0-address-address-line2"]',
        ).value = shippingAddress?.address?.addressLine2 ?? '';
        return true;
      }
      return false;
    }

    /**
     * Initializes a checkout pane.
     *
     * @param {*} pane
     *   The checkout pane.
     */
    async initializeCheckoutPane(pane) {
      const event = new CustomEvent('initializeCheckoutPane', {
        bubbles: true,
        cancelable: false,
        detail: {},
      });
      pane
        .querySelector('button[data-checkout-button-type="continue"]')
        ?.addEventListener('click', async () => {
          await this.closeCheckoutPane(pane, true);
        });
      pane
        .querySelector('button[data-checkout-button-type="edit"]')
        ?.addEventListener('click', async () => {
          await this.openCheckoutPane(pane);
        });
      pane.dataset.initialized = 'true';
      pane.dispatchEvent(event);
    }

    /**
     * Adds listeners to the shipping checkout pane.
     *
     * Ajax calls will bring in a new version of the pane,
     * so we need to be able to add the listeners again.
     *
     * @param {*} pane
     *   The checkout pane.
     */
    addListenersToShippingCheckoutPane(pane) {
      pane.addEventListener('initializeCheckoutPane', async (event) => {
        const shippingPane = event.target;
        const shippingRecalculateButton = shippingPane.querySelector(
          '*[data-drupal-selector="edit-shipping-information-recalculate-shipping"]',
        );
        shippingRecalculateButton.style.display = 'none';
        const editButton = shippingPane.querySelector(
          'button[data-checkout-button-type="edit"]',
        );
        const changeButton = shippingPane.querySelector(
          'button[data-checkout-button-type="change"]',
        );
        const shipmentMethod = shippingPane.querySelector(
          '*[data-drupal-selector="edit-shipping-information-shipments"]',
        );
        if (this.memberAuthenticatedSuccessfully) {
          editButton.style.display = 'none';
          changeButton.style.display = '';
          shipmentMethod.style.display = 'block';
        } else {
          editButton.style.display = '';
          changeButton.style.display = 'none';
          shipmentMethod.style.display = '';
        }
        if (this.settings.shippingInformation?.address) {
          const { address } = this.settings.shippingInformation;
          this.shippingAddress = {
            name: {
              firstName: address.given_name,
              lastName: address.family_name,
            },
            address: {
              countryCode: address.country_code,
              postalCode: address.postal_code,
              adminArea2: address.locality,
              adminArea1: address.administrative_area,
              addressLine1: address.address_line1,
              addressLine2: address.address_line2,
              company: address.organization,
            },
            phoneNumber: {
              countryCode: null,
              nationalNumber: null,
            },
          };
          this.paymentComponent?.setShippingAddress(this.shippingAddress);
        }
        if (this.memberAuthenticatedSuccessfully && this.shippingAddress) {
          if (this.activeCheckoutPaneId === 'shipping_information') {
            await this.openCheckoutPane(this.getNextCheckoutPane(shippingPane));
          }
        }
        shippingPane
          .querySelector('button[data-checkout-button-type="change"]')
          ?.addEventListener('click', async (clickEvent) => {
            clickEvent.preventDefault();
            if (this.memberAuthenticatedSuccessfully) {
              // Open Shipping Address Selector for Fastlane members.
              const { selectionChanged, selectedAddress } =
                await this.profile.showShippingAddressSelector();

              if (selectionChanged) {
                // Update state & form UI.
                this.shippingAddress = selectedAddress;
                await this.populateShippingInformationProfileForm(
                  this.shippingAddress,
                );
                if (
                  this.shippingAddress &&
                  this.shippingAddress.address.postalCode
                ) {
                  this.paymentComponent?.setShippingAddress(
                    this.shippingAddress,
                  );
                }
                await CommercePaypalFastlane.validateCheckoutPane(
                  this.getShippingCheckoutPane(),
                );
              } else {
                // Selection modal was dismissed without selection.
              }
            }
          });
      });

      pane.addEventListener('validateCheckoutPane', async (event) => {
        const shippingPane = event.target;
        const controls = CommercePaypalFastlane.getControls(shippingPane);
        let validated = true;
        /* eslint-disable-next-line no-restricted-syntax */
        for (const control of controls) {
          if (!control.checkValidity()) {
            control.reportValidity();
            control.scrollIntoView({ behavior: 'smooth', block: 'center' });
            event.preventDefault();
            validated = false;
            break;
          }
        }
        if (validated) {
          const shippingRecalculateButton = shippingPane.querySelector(
            '*[data-drupal-selector="edit-shipping-information-recalculate-shipping"]',
          );
          shippingRecalculateButton?.dispatchEvent(new Event('mousedown'));
        }
        return validated;
      });

      pane.addEventListener('updateCheckoutPaneSummary', async (event) => {
        // We don't want the default update to occur.
        event.detail.updated = true;
      });
    }

    /**
     * Open a checkout pane.
     *
     * @param {*} pane
     *   The checkout pane.
     * @return {Promise<boolean>}
     *   Whether the pane was opened.
     */
    async openCheckoutPane(pane) {
      if (this.activeCheckoutPaneId) {
        if (
          !(await this.closeCheckoutPane(
            this.getCheckoutPane(this.activeCheckoutPaneId),
          ))
        ) {
          return false;
        }
      }
      const event = new CustomEvent('openCheckoutPane', {
        bubbles: true,
        cancelable: true,
        detail: {},
      });
      const cancelled = !pane.dispatchEvent(event);
      // If an event handler calls preventDefault(), we will not
      // open the checkout pane.
      if (cancelled) {
        return false;
      }
      pane.classList.add('active');
      CommercePaypalFastlane.focusCheckoutPane(pane);
      this.activeCheckoutPaneId = pane.dataset.checkoutPaneId;
      if (!this.visitedPaneIds.includes(pane.dataset.checkoutPaneId)) {
        this.visitedPaneIds.push(pane.dataset.checkoutPaneId);
      }
      pane.classList.add('visited');
      return true;
    }

    /**
     * Set focus to the first control in the pane.
     *
     * @param {*} pane
     *   The checkout pane.
     */
    static focusCheckoutPane(pane) {
      const firstControl = CommercePaypalFastlane.getControls(pane)[0] ?? pane;
      firstControl?.scrollIntoView({ block: 'center' });
      firstControl?.focus();
    }

    /**
     * Validate a checkout pane.
     *
     * @param {*} pane
     *   The checkout pane.
     * @return {Promise<boolean>}
     *   Whether the pane is valid.
     */
    static async validateCheckoutPane(pane) {
      const event = new CustomEvent('validateCheckoutPane', {
        bubbles: true,
        cancelable: true,
        detail: { validated: null },
      });
      const cancelled = !pane.dispatchEvent(event);
      if (cancelled) {
        return false;
      }
      let { validated } = event.detail;
      // If no event handler set the validated property,
      // we will perform validation of all controls.
      if (validated === null) {
        validated = true;
        const controls = CommercePaypalFastlane.getControls(pane);
        /* eslint-disable-next-line no-restricted-syntax */
        for (const control of controls) {
          if (!control.checkValidity()) {
            control.reportValidity();
            control.scrollIntoView({ block: 'center' });
            validated = false;
            break;
          }
        }
      }
      return validated;
    }

    /**
     * Close a checkout pane.
     *
     * @param {*} pane
     *   The pane to close.
     * @param {boolean} validate
     *   Whether to validate and open the next pane.
     * @return {Promise<boolean>}
     *   Whether the pane was closed.
     */
    async closeCheckoutPane(pane, validate = false) {
      const event = new CustomEvent('closeCheckoutPane', {
        bubbles: true,
        cancelable: true,
        detail: {},
      });
      const cancelled = !pane.dispatchEvent(event);
      if (cancelled) {
        return false;
      }
      if (validate) {
        const validated =
          await CommercePaypalFastlane.validateCheckoutPane(pane);
        if (validated) {
          pane.dataset.validated = 'true';
          await CommercePaypalFastlane.updateCheckoutPaneSummary(pane);
          this.activeCheckoutPaneId = null;
          let nextCheckoutPane = this.getNextCheckoutPane(pane);
          while (
            nextCheckoutPane &&
            (nextCheckoutPane.classList.contains('visited') ||
              nextCheckoutPane.classList.contains('pinned'))
          ) {
            nextCheckoutPane = this.getNextCheckoutPane(nextCheckoutPane);
          }
          if (nextCheckoutPane) {
            await this.openCheckoutPane(nextCheckoutPane);
          } else {
            this.form
              .querySelector('input[data-drupal-selector="edit-actions-next"]')
              .focus();
          }
          pane.classList.remove('active');
        } else {
          delete pane.dataset.validated;
          return false;
        }
      } else {
        pane.classList.remove('active');
      }
      return true;
    }

    /**
     * Update a checkout pane's summary.
     *
     * @param {*} pane
     *   The checkout pane.
     * @return {Promise<boolean>}
     *   Whether the pane was updated.
     */
    static async updateCheckoutPaneSummary(pane) {
      const event = new CustomEvent('updateCheckoutPaneSummary', {
        bubbles: true,
        cancelable: true,
        detail: { updated: null },
      });
      const cancelled = !pane.dispatchEvent(event);
      if (cancelled) {
        return false;
      }
      if (event.detail.updated) {
        return true;
      }
      const controls = CommercePaypalFastlane.getControls(pane);
      let summary = '';
      /* eslint-disable-next-line no-restricted-syntax */
      for (const control of controls) {
        if (control.value) {
          summary += `<div class="field__label">${control.labels[0]?.innerHTML}</div><div class="field__item">${control.value}</div></div>`;
        }
      }
      if (summary) {
        CommercePaypalFastlane.getPaneSummary(pane).querySelector(
          'div',
        ).innerHTML = summary;
      }
      return true;
    }

    /**
     * Initialize the checkout flow.
     *
     * @param {*} settings
     *   The settings.
     */
    async initializeCheckout(settings) {
      const fastlaneOptions = {};
      this.settings = settings;

      this.form = document.querySelector(
        'form.commerce-checkout-flow-paypal-fastlane',
      );
      this.form
        .querySelector(
          '[data-checkout-pane-id="paypal_fastlane_contact_information"]',
        )
        .addEventListener('validateCheckoutPane', async (event) => {
          const emailControl = event.target.querySelector(
            '*[data-drupal-selector="edit-paypal-fastlane-contact-information-email"]',
          );
          if (emailControl.checkValidity()) {
            if (emailControl.value !== this.email) {
              this.email = emailControl.value;
              await this.lookupFastlaneMember(this.email);
              event.detail.validated = true;
            }
          } else {
            emailControl.reportValidity();
            event.preventDefault();
            event.detail.validated = false;
          }
        });

      const shippingPane = this.form.querySelector(
        '[data-checkout-pane-id="shipping_information"]',
      );
      if (shippingPane) {
        this.addListenersToShippingCheckoutPane(shippingPane);
      }

      this.form
        .querySelector('[data-checkout-pane-id="payment_information"]')
        .addEventListener('validateCheckoutPane', async (event) => {
          try {
            this.paymentToken = await this.paymentComponent
              ?.getPaymentToken()
              .catch(() => {
                return null;
              });
            if (!this.paymentToken) {
              event.preventDefault();
            }
          } catch (e) {
            event.preventDefault();
            if (this.activeCheckoutPaneId !== 'payment_information') {
              await this.openCheckoutPane(
                this.getCheckoutPane('payment_information'),
              );
            }
          }
        });

      /* eslint-disable-next-line no-restricted-syntax */
      for (const checkoutPane of this.getCheckoutPanes()) {
        /* eslint-disable-next-line no-await-in-loop */
        await this.initializeCheckoutPane(checkoutPane);
      }

      await this.openCheckoutPane(this.getCheckoutPanes()[0]);

      if (this.settings.allowedBillingCountries?.length) {
        // Restricting billing countries is not currently supported.
      }
      if (
        this.settings.hasShipments &&
        this.settings.allowedShippingCountries?.length
      ) {
        fastlaneOptions.shippingAddressOptions = {
          allowedLocations: settings.allowedShippingCountries,
        };
      }
      if (this.settings.allowedBrands?.length) {
        fastlaneOptions.cardOptions = {
          allowedBrands: this.settings.allowedBrands,
        };
      }
      // Instantiates the Fastlane module.
      const fastlane = await window.paypal.Fastlane(fastlaneOptions);
      // A limited number of languages are supported.
      fastlane.setLocale(this.settings.locale ?? 'en_us');
      const {
        identity,
        profile,
        FastlanePaymentComponent,
        FastlaneWatermarkComponent,
      } = fastlane;
      this.profile = profile;
      this.identity = identity;
      this.FastlanePaymentComponent = FastlanePaymentComponent;
      (
        await FastlaneWatermarkComponent({
          includeAdditionalInfo: true,
        })
      ).render('#watermark-container');

      // Event listener when the user clicks to place the order.
      this.form.addEventListener('submit', async (event) => {
        if (this.allowSubmit) {
          return true;
        }
        event.preventDefault();
        try {
          await this.showOverlay();
          /* eslint-disable-next-line no-restricted-syntax */
          for (const pane of this.getCheckoutPanes()) {
            if (!pane.dataset.validated) {
              /* eslint-disable-next-line no-await-in-loop */
              await this.openCheckoutPane(pane);
            }
          }
          if (!this.paymentToken) {
            this.paymentToken = await this.paymentComponent?.getPaymentToken();
          }
          if (this.paymentToken) {
            this.allowSubmit = true;
          }
          this.form.querySelector('[data-fastlane-payment-token]').value =
            JSON.stringify(this.paymentToken);
          if (this.settings.hasShipments) {
            this.form.querySelector('[data-fastlane-shipping-address]').value =
              JSON.stringify(this.shippingAddress);
          }
          this.form.submit();
        } catch (e) {
          this.overlayModal?.close();
        }
      });
    }

    /**
     * Look up a Fastlane by PayPal member.
     *
     * @param {*} email
     *   The email address.
     */
    async lookupFastlaneMember(email) {
      await this.showOverlay();
      const identityResult = await this.identity.lookupCustomerByEmail(email);
      const { customerContextId } = identityResult;
      this.overlayModal?.close();

      if (customerContextId) {
        // Email is associated with a Fastlane member or a PayPal member,
        // send customerContextId to trigger the authentication flow.
        const authResponse =
          await this.identity.triggerAuthenticationFlow(customerContextId);

        if (authResponse?.authenticationState === 'succeeded') {
          // Fastlane member successfully authenticated themselves
          // profileData contains their profile details
          this.memberAuthenticatedSuccessfully = true;
          this.form.classList.add('member');
          this.profileData = authResponse?.profileData;
          if (this.settings.hasShipments) {
            this.shippingAddress = this.profileData?.shippingAddress ?? null;
          }
          this.paymentToken = this.profileData?.card;
          this.getPaymentCheckoutPane().classList.add('pinned');
        } else {
          this.getPaymentCheckoutPane().classList.remove('pinned');
          // Member failed or canceled authentication.
          // Treat them as a guest payer.
          this.memberAuthenticatedSuccessfully = false;
          this.form.classList.remove('member');
        }
      } else {
        // No profile found with this email address. This is a guest payer.
        this.memberAuthenticatedSuccessfully = false;
        this.form.classList.remove('member');
      }
      if (!this.paymentComponent) {
        const paymentComponentOptions = {
          styles: this.settings.styles,
        };
        this.paymentComponent = await this.FastlanePaymentComponent(
          paymentComponentOptions,
        );
        await this.paymentComponent?.render(
          '*[data-drupal-selector="edit-payment-information-add-payment-method-payment-details-fastlane-payment-container"]',
        );
      }
      if (this.settings.hasShipments) {
        // Populate the shipping information profile pane.
        if (this.memberAuthenticatedSuccessfully) {
          await this.populateShippingInformationProfileForm(
            this.shippingAddress ?? {},
          );
          if (this.shippingAddress && this.shippingAddress.address.postalCode) {
            this.paymentComponent?.setShippingAddress(this.shippingAddress);
          }
          await CommercePaypalFastlane.validateCheckoutPane(
            this.getShippingCheckoutPane(),
          );
        } else {
          const shippingPane = this.getShippingCheckoutPane();
          const editButton = shippingPane.querySelector(
            'button[data-checkout-button-type="edit"]',
          );
          const changeButton = shippingPane.querySelector(
            'button[data-checkout-button-type="change"]',
          );
          const shipmentMethod = shippingPane.querySelector(
            '*[data-drupal-selector="edit-shipping-information-shipments"]',
          );
          editButton.style.display = '';
          changeButton.style.display = 'none';
          shipmentMethod.style.display = '';
        }
      }
    }

    /**
     * Show the overlay.
     */
    async showOverlay() {
      const overlayTitle = Drupal.t('Fastlane by PayPal');
      const overlayContent = Drupal.t('One moment, please!');
      const overlayModal = Drupal.dialog(
        `<div><span>${overlayContent}</span></div>`,
        {
          title: overlayTitle,
          classes: {
            'ui-dialog': 'fastlane-lookup-overlay',
          },
          width: 800,
          resizable: false,
          closeOnEscape: false,
          top: 0,
          left: 0,
          beforeClose: false,
          close() {
            overlayModal.close();
          },
        },
      );
      this.overlayModal = overlayModal;
      this.overlayModal.showModal();
    }

    /**
     * Get the summary container for the pane.
     *
     * @param {*} pane
     *   The checkout pane.
     * @return {*}
     *   The checkout pane summary.
     */
    static getPaneSummary(pane) {
      return pane.querySelector('.summary');
    }

    /**
     * Gets the next checkout pane, following the specified one.
     *
     * @param {*} pane
     *   The checkout pane.
     * @return {*}
     *   The next checkout pane or null, if this is the last one.
     */
    getNextCheckoutPane(pane) {
      const checkoutPanes = this.getCheckoutPanes();
      const currentIndex = Array.prototype.indexOf.call(checkoutPanes, pane);
      return checkoutPanes[currentIndex + 1];
    }

    /**
     * Gets the payment information checkout pane.
     *
     * @return {*}
     *   The payment checkout pane.
     */
    getPaymentCheckoutPane() {
      return this.getCheckoutPane('payment_information');
    }

    /**
     * Gets the shipping information checkout pane.
     *
     * @return {*}
     *   The shipping checkout pane.
     */
    getShippingCheckoutPane() {
      return this.getCheckoutPane('shipping_information');
    }

    /**
     * Return a specific checkout pane, given the checkout pane id.
     * @param {*} checkoutPaneId
     *   The checkout pane id.
     * @return {*}
     *   The checkout pane or null.
     */
    getCheckoutPane(checkoutPaneId) {
      return this.form.querySelector(
        `.layout-region-checkout-main *[data-checkout-pane-id="${checkoutPaneId}"]`,
      );
    }

    /**
     * Return the checkout panes in the main region.
     *
     * @return {*}
     *   The checkout panes in the main region.
     */
    getCheckoutPanes() {
      // We are interested in the panes in the main area, not the sidebar.
      return this.form.querySelectorAll(
        '.layout-region-checkout-main *[data-checkout-pane-id]',
      );
    }

    /**
     * Reinitialize a checkout pane.
     *
     * @param {*} item
     *   The possible pane.
     */
    async reinitializeCheckoutPane(item) {
      let checkoutPane = null;
      if (item.dataset?.checkoutPaneId) {
        checkoutPane = item;
      } else if (item.firstElementChild?.dataset.checkoutPaneId) {
        checkoutPane = item.firstElementChild;
      }
      if (!checkoutPane) {
        return;
      }
      if (checkoutPane.dataset?.checkoutPaneId === 'shipping_information') {
        this.addListenersToShippingCheckoutPane(checkoutPane);
      }
      await this.initializeCheckoutPane(checkoutPane);
      if (checkoutPane.dataset.checkoutPaneId === this.activeCheckoutPaneId) {
        checkoutPane.classList.add('active');
      }
      if (this.visitedPaneIds.includes(checkoutPane.dataset.checkoutPaneId)) {
        checkoutPane.classList.add('visited');
      }
    }

    /**
     * Get the user controls in the container.
     *
     * @param {*} container
     *   The container to search.
     * @return {*}
     *   The controls in the container.
     */
    static getControls(container) {
      return container.querySelectorAll(
        'input:not([type="hidden"]):not([type="submit"]),textarea,select',
      );
    }
  }

  Drupal.behaviors.commercePaypalFastlane = {
    async attach(context, drupalSettings) {
      // Validate all required dependencies.
      if (!Drupal || !drupalSettings) {
        console.error('Required dependencies are not available');
        return;
      }

      const [settings] = Object.values(drupalSettings.paypalFastlane);
      if (!settings?.clientToken || !settings?.elementId) {
        console.error('Required settings are missing');
        return;
      }

      const [element] = once(
        'paypal-fastlane-processed',
        `#${settings.elementId}`,
        context,
      );
      if (element) {
        const fastlaneComponent = new CommercePaypalFastlane();
        await fastlaneComponent.initializeFastlane(settings);
        Drupal.CommerceFastlaneInstances.set(element, fastlaneComponent);
      } else {
        const [fastlaneComponent] = Drupal.CommerceFastlaneInstances.values();
        if (fastlaneComponent) {
          await fastlaneComponent.reinitializeCheckoutPane(context);
        }
      }
    },
  };
})(Drupal, once);
