/**
 * AI Events Pro - Updated Admin JavaScript
 */

(function ($) {
  "use strict";

  class AIEventsAdmin {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.initTooltips();

      // Auto-restore last active tab
      const lastTab = localStorage.getItem("ai-events-active-tab");
      if (lastTab && $(lastTab).length) {
        this.switchToTab(lastTab);
      }
    }

    bindEvents() {
      // Tab switching
      $(document).on("click", ".nav-tab", this.handleTabSwitch.bind(this));

      // API testing
      $(document).on(
        "click",
        ".test-api-btn",
        this.testApiConnection.bind(this)
      );

      // Event syncing
      $(document).on("click", "#sync-events-btn", this.syncEvents.bind(this));

      // Cache clearing
      $(document).on("click", "#clear-cache-btn", this.clearCache.bind(this));

      // Form validation
      $(document).on("submit", "form", this.validateForm.bind(this));

      // Real-time validation
      $(document).on("input", 'input[type="url"]', this.validateUrl);
      $(document).on("input", 'input[type="email"]', this.validateEmail);
      $(document).on("input", "input[required]", this.validateRequired);

      // Settings auto-save indicator
      $(document).on(
        "change",
        "input, select, textarea",
        this.showUnsavedChanges
      );
    }

    handleTabSwitch(e) {
      e.preventDefault();

      const $tab = $(e.currentTarget);
      const target = $tab.attr("href");

      this.switchToTab(target);
      localStorage.setItem("ai-events-active-tab", target);
    }

    switchToTab(target) {
      $(".nav-tab").removeClass("nav-tab-active");
      $(`a[href="${target}"]`).addClass("nav-tab-active");
      $(".tab-content").removeClass("active");
      $(target).addClass("active");
    }

    async testApiConnection(e) {
      const $button = $(e.currentTarget);
      const apiType = $button.data("api");
      const optionName = $button.data("option");
      const fieldName = $button.data("field");
      const $input = $button.prev("input");
      const apiKey = $input.val().trim();

      if (!apiKey) {
        this.showNotification("Please enter an API key first.", "error");
        return;
      }

      // Show loading state
      const originalText = $button.text();
      $button.prop("disabled", true).text("Testing...").addClass("testing");

      try {
        const response = await $.post(ajaxurl, {
          action: "test_api_connection",
          nonce: ai_events_admin.nonce,
          api_type: apiType,
          option_name: optionName,
          field_name: fieldName,
          api_key: apiKey,
        });

        if (response.success) {
          this.showNotification(response.data, "success");
          $button.addClass("success");
          $input.addClass("valid");
        } else {
          this.showNotification("Connection failed: " + response.data, "error");
          $button.addClass("error");
          $input.addClass("invalid");
        }
      } catch (error) {
        this.showNotification(
          "Connection failed: " + error.statusText,
          "error"
        );
        $button.addClass("error");
      } finally {
        // Reset button
        setTimeout(() => {
          $button
            .prop("disabled", false)
            .text(originalText)
            .removeClass("testing success error");
        }, 3000);
      }
    }

    async syncEvents(e) {
      const $button = $(e.currentTarget);
      const $results = $("#sync-results");

      const location = $("#sync_location").val().trim();
      const radius = $("#sync_radius").val();
      const limit = $("#sync_limit").val();

      if (!location) {
        this.showNotification(
          "Please enter a location to sync events.",
          "error"
        );
        $("#sync_location").focus();
        return;
      }

      // Show loading state
      $button.prop("disabled", true).text("Syncing Events...");
      $results.removeClass("success error").hide();

      // Show progress indicator
      this.showProgressIndicator("Connecting to APIs...");

      try {
        const response = await $.post(ajaxurl, {
          action: "sync_events",
          nonce: ai_events_admin.nonce,
          location: location,
          radius: radius,
          limit: limit,
        });

        this.hideProgressIndicator();

        if (response.success) {
          let html = `<div class="sync-success">
                        <strong>✅ ${response.data.message}</strong>
                        <p>Total events synced: ${response.data.events_count}</p>
                    </div>`;

          if (
            response.data.events_preview &&
            response.data.events_preview.length > 0
          ) {
            html +=
              '<div class="events-preview"><h4>Preview of synced events:</h4><ul>';
            response.data.events_preview.forEach((event) => {
              html += `<li><strong>${event.title}</strong> - ${event.date} (${event.source})</li>`;
            });
            html += "</ul></div>";
          }

          $results.addClass("success").html(html).slideDown();
          this.showNotification("Events synced successfully!", "success");
        } else {
          const errorHtml = `<div class="sync-error">
                        <strong>❌ Sync Failed</strong>
                        <p>${response.data}</p>
                        <details>
                            <summary>Troubleshooting Tips</summary>
                            <ul>
                                <li>Check that your API credentials are correct</li>
                                <li>Verify that you have enabled the desired event sources</li>
                                <li>Try a different location or larger radius</li>
                                <li>Check the error logs for more details</li>
                            </ul>
                        </details>
                    </div>`;

          $results.addClass("error").html(errorHtml).slideDown();
          this.showNotification("Sync failed: " + response.data, "error");
        }
      } catch (error) {
        this.hideProgressIndicator();
        $results
          .addClass("error")
          .html(`<strong>❌ Network Error:</strong> ${error.statusText}`)
          .slideDown();
        this.showNotification("Network error occurred", "error");
      } finally {
        $button.prop("disabled", false).text("Sync Events Now");
      }
    }

    async clearCache(e) {
      if (
        !confirm(
          "Are you sure you want to clear all cached events? This cannot be undone."
        )
      ) {
        return;
      }

      const $button = $(e.currentTarget);
      const $results = $("#sync-results");

      $button.prop("disabled", true).text("Clearing Cache...");

      try {
        const response = await $.post(ajaxurl, {
          action: "clear_events_cache",
          nonce: ai_events_admin.nonce,
        });

        if (response.success) {
          $results
            .removeClass("error")
            .addClass("success")
            .html(`<strong>✅ ${response.data}</strong>`)
            .slideDown();
          this.showNotification("Cache cleared successfully!", "success");
        } else {
          $results
            .removeClass("success")
            .addClass("error")
            .html(`<strong>❌ Error:</strong> ${response.data}`)
            .slideDown();
          this.showNotification("Failed to clear cache", "error");
        }
      } catch (error) {
        $results
          .removeClass("success")
          .addClass("error")
          .html(`<strong>❌ Error:</strong> Network error occurred`)
          .slideDown();
        this.showNotification("Network error occurred", "error");
      } finally {
        $button.prop("disabled", false).text("Clear Cache");
      }
    }

    validateForm(e) {
      const $form = $(e.currentTarget);
      let isValid = true;
      let firstInvalidField = null;

      // Validate required fields
      $form.find("[required]").each(function () {
        const $field = $(this);
        if (!$field.val().trim()) {
          $field.addClass("invalid");
          isValid = false;
          if (!firstInvalidField) {
            firstInvalidField = $field;
          }
        } else {
          $field.removeClass("invalid");
        }
      });

      // Validate URLs
      $form.find('input[type="url"]').each(function () {
        const $field = $(this);
        const url = $field.val().trim();

        if (url && !isValidUrl(url)) {
          $field.addClass("invalid");
          isValid = false;
          if (!firstInvalidField) {
            firstInvalidField = $field;
          }
        } else {
          $field.removeClass("invalid");
        }
      });

      // Validate emails
      $form.find('input[type="email"]').each(function () {
        const $field = $(this);
        const email = $field.val().trim();

        if (email && !isValidEmail(email)) {
          $field.addClass("invalid");
          isValid = false;
          if (!firstInvalidField) {
            firstInvalidField = $field;
          }
        } else {
          $field.removeClass("invalid");
        }
      });

      if (!isValid) {
        e.preventDefault();
        this.showNotification(
          "Please correct the highlighted fields.",
          "error"
        );
        if (firstInvalidField) {
          firstInvalidField.focus();
        }
      }

      return isValid;
    }

    validateUrl(e) {
      const $field = $(e.currentTarget);
      const url = $field.val().trim();

      if (url && !isValidUrl(url)) {
        $field.addClass("invalid");
      } else {
        $field.removeClass("invalid");
      }
    }

    validateEmail(e) {
      const $field = $(e.currentTarget);
      const email = $field.val().trim();

      if (email && !isValidEmail(email)) {
        $field.addClass("invalid");
      } else {
        $field.removeClass("invalid");
      }
    }

    validateRequired(e) {
      const $field = $(e.currentTarget);

      if ($field.val().trim()) {
        $field.removeClass("invalid");
      }
    }

    showUnsavedChanges() {
      if (!$(".unsaved-changes-notice").length) {
        $('<div class="unsaved-changes-notice">You have unsaved changes.</div>')
          .prependTo(".wrap")
          .hide()
          .slideDown();
      }
    }

    showNotification(message, type = "info") {
      const $notification = $(`
                <div class="ai-events-notification ${type}">
                    <span class="message">${message}</span>
                    <button type="button" class="close-notification">&times;</button>
                </div>
            `);

      $("body").append($notification);

      setTimeout(() => $notification.addClass("show"), 100);

      // Auto-remove after 5 seconds
      const timeout = setTimeout(
        () => this.removeNotification($notification),
        5000
      );

      // Manual close
      $notification.find(".close-notification").on("click", () => {
        clearTimeout(timeout);
        this.removeNotification($notification);
      });
    }

    removeNotification($notification) {
      $notification.addClass("hide");
      setTimeout(() => $notification.remove(), 300);
    }

    showProgressIndicator(message) {
      const $indicator = $(`
                <div class="ai-events-progress">
                    <div class="progress-spinner"></div>
                    <span class="progress-message">${message}</span>
                </div>
            `);

      $("body").append($indicator);
      setTimeout(() => $indicator.addClass("show"), 100);
    }

    hideProgressIndicator() {
      $(".ai-events-progress").addClass("hide");
      setTimeout(() => $(".ai-events-progress").remove(), 300);
    }

    initTooltips() {
      $("[data-tooltip]").each(function () {
        const $element = $(this);
        const tooltip = $element.data("tooltip");

        $element.on("mouseenter", function () {
          const $tooltip = $(`<div class="ai-events-tooltip">${tooltip}</div>`);
          $("body").append($tooltip);

          const offset = $element.offset();
          $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 5,
            left:
              offset.left +
              $element.outerWidth() / 2 -
              $tooltip.outerWidth() / 2,
          });
        });

        $element.on("mouseleave", function () {
          $(".ai-events-tooltip").remove();
        });
      });
    }
  }

  // Utility functions
  function isValidUrl(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  }

  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Initialize when document is ready
  $(document).ready(function () {
    new AIEventsAdmin();

    // Remove unsaved changes notice when form is submitted
    $(document).on("submit", "form", function () {
      $(".unsaved-changes-notice").remove();
    });
  });
})(jQuery);

// Add CSS for notifications and progress indicators
const adminStyles = `
<style>
.ai-events-notification {
    position: fixed;
    top: 32px;
    right: 20px;
    background: #fff;
    border-left: 4px solid #0073aa;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 15px 20px;
    margin-bottom: 10px;
    max-width: 400px;
    z-index: 100000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.ai-events-notification.show {
    transform: translateX(0);
}

.ai-events-notification.hide {
    transform: translateX(100%);
}

.ai-events-notification.success {
    border-left-color: #46b450;
}

.ai-events-notification.error {
    border-left-color: #dc3232;
}

.ai-events-notification .close-notification {
    background: none;
    border: none;
    position: absolute;
    top: 5px;
    right: 10px;
    font-size: 16px;
    cursor: pointer;
    color: #666;
}

.ai-events-progress {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 20px 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 100001;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.ai-events-progress.show {
    opacity: 1;
}

.ai-events-progress.hide {
    opacity: 0;
}

.progress-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.unsaved-changes-notice {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 12px 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.test-api-btn.testing {
    opacity: 0.6;
}

.test-api-btn.success {
    background: #46b450;
    border-color: #46b450;
    color: white;
}

.test-api-btn.error {
    background: #dc3232;
    border-color: #dc3232;
    color: white;
}

input.invalid {
    border-color: #dc3232 !important;
    box-shadow: 0 0 0 1px #dc3232;
}

input.valid {
    border-color: #46b450 !important;
    box-shadow: 0 0 0 1px #46b450;
}

.ai-events-tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 100000;
    white-space: nowrap;
}

.sync-success, .sync-error {
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.sync-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.sync-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.events-preview {
    margin-top: 15px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 4px;
}

.events-preview h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.events-preview ul {
    margin: 0;
    padding-left: 20px;
}

.events-preview li {
    margin-bottom: 5px;
    font-size: 13px;
}

details {
    margin-top: 10px;
}

summary {
    cursor: pointer;
    font-weight: bold;
}

details ul {
    margin-top: 10px;
    padding-left: 20px;
}
</style>
`;

document.head.insertAdjacentHTML("beforeend", adminStyles);
