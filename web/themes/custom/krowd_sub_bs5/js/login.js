(function ($, Drupal) {
  Drupal.behaviors.buletinModal = {
    attach: function (context, settings) {
      document.addEventListener('DOMContentLoaded', () => {
        // --- Elements ---
        const loginForm = document.getElementById('loginForm');
        const identityInput = document.getElementById('identity');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const spinner = document.querySelector('.spinner');

        // --- Password Visibility Toggle ---
        togglePasswordBtn.addEventListener('click', () => {
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);

          // Toggle icons
          const eyeOpen = document.getElementById('icon-eye-open');
          const eyeClosed = document.getElementById('icon-eye-closed');

          if (type === 'text') {
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
          } else {
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
          }
        });

        // --- Form Submission ---
        loginForm.addEventListener('submit', (e) => {
          e.preventDefault();

          // Clear previous errors
          document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
          let isValid = true;

          // Validate Identity
          if (!identityInput.value.trim()) {
            document.getElementById('error-identity').style.display = 'block';
            isValid = false;
          }

          // Validate Password
          if (!passwordInput.value.trim()) {
            document.getElementById('error-password').style.display = 'block';
            isValid = false;
          }

          if (!isValid) return;

          // Start Loading State
          setLoading(true);

          // Simulate API Request
          setTimeout(() => {
            setLoading(false);

            // Mock logic: if ID > 3 chars and Pass > 3 chars -> Success
            if (identityInput.value.length > 3 && passwordInput.value.length > 3) {
              showToast('success', 'Login Successful', 'Redirecting to dashboard...');
            } else {
              showToast('error', 'Authentication Failed', 'Invalid credentials provided.');
            }
          }, 1500);
        });

        // --- Helper: Loading State ---
        function setLoading(isLoading) {
          if (isLoading) {
            submitBtn.disabled = true;
            btnText.textContent = 'Signing In...';
            spinner.style.display = 'block';
          } else {
            submitBtn.disabled = false;
            btnText.textContent = 'Sign In';
            spinner.style.display = 'none';
          }
        }

        // --- Helper: Toast Notification ---
        function showToast(type, title, message) {
          const container = document.getElementById('toast-container');

          // Create Toast Element
          const toast = document.createElement('div');
          toast.className = `toast ${type}`;

          // Icon SVG based on type
          const iconSvg = type === 'success'
            ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`
            : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

          toast.innerHTML = `
                  <div class="toast-icon">${iconSvg}</div>
                  <div class="toast-content">
                      <h4>${title}</h4>
                      <p>${message}</p>
                  </div>
              `;

          container.appendChild(toast);

          // Remove after 3 seconds
          setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in forwards';
            toast.addEventListener('animationend', () => {
              toast.remove();
            });
          }, 3000);
        }
      });
    }
  };
})(jQuery, Drupal);
