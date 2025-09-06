/**
 * AI Events Pro - Theme Switcher
 */

class AIEventsThemeSwitcher {
  constructor() {
    this.currentTheme = "auto";
    this.init();
  }

  init() {
    this.loadSavedTheme();
    this.bindEvents();
    this.applyTheme();
  }

  loadSavedTheme() {
    // Check for saved theme in localStorage
    const savedTheme = localStorage.getItem("ai-events-theme");
    if (savedTheme) {
      this.currentTheme = savedTheme;
    } else {
      // Check for cookie (for logged-in users)
      const cookies = document.cookie.split(";");
      for (let cookie of cookies) {
        const [name, value] = cookie.trim().split("=");
        if (name === "ai_events_theme_mode") {
          this.currentTheme = value;
          break;
        }
      }
    }
  }

  bindEvents() {
    const toggleButton = document.getElementById("theme-toggle");
    if (toggleButton) {
      toggleButton.addEventListener("click", () => this.toggleTheme());
    }

    // Listen for system theme changes
    if (window.matchMedia) {
      window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", () => {
          if (this.currentTheme === "auto") {
            this.applyTheme();
          }
        });
    }
  }

  toggleTheme() {
    const themes = ["light", "dark", "auto"];
    const currentIndex = themes.indexOf(this.currentTheme);
    const nextIndex = (currentIndex + 1) % themes.length;

    this.currentTheme = themes[nextIndex];
    this.saveTheme();
    this.applyTheme();
    this.updateToggleButton();

    // Send to server if user is logged in
    this.syncWithServer();
  }

  applyTheme() {
    const body = document.body;
    const html = document.documentElement;

    // Remove existing theme classes
    body.classList.remove("ai-events-theme-light", "ai-events-theme-dark");
    html.removeAttribute("data-theme");

    let effectiveTheme = this.currentTheme;

    if (this.currentTheme === "auto") {
      // Detect system preference
      if (
        window.matchMedia &&
        window.matchMedia("(prefers-color-scheme: dark)").matches
      ) {
        effectiveTheme = "dark";
      } else {
        effectiveTheme = "light";
      }
    }

    // Apply theme
    if (effectiveTheme === "dark") {
      body.classList.add("ai-events-theme-dark");
      html.setAttribute("data-theme", "dark");
    } else {
      body.classList.add("ai-events-theme-light");
      html.setAttribute("data-theme", "light");
    }

    this.updateToggleButton();
  }

  updateToggleButton() {
    const button = document.getElementById("theme-toggle");
    if (!button) return;

    const lightIcon = button.querySelector(".light-icon");
    const darkIcon = button.querySelector(".dark-icon");

    if (!lightIcon || !darkIcon) return;

    // Update button appearance based on current theme
    let effectiveTheme = this.currentTheme;

    if (this.currentTheme === "auto") {
      effectiveTheme =
        window.matchMedia &&
        window.matchMedia("(prefers-color-scheme: dark)").matches
          ? "dark"
          : "light";
    }

    if (effectiveTheme === "dark") {
      lightIcon.style.opacity = "0";
      darkIcon.style.opacity = "1";
      button.setAttribute("aria-label", "Switch to light mode");
    } else {
      lightIcon.style.opacity = "1";
      darkIcon.style.opacity = "0";
      button.setAttribute("aria-label", "Switch to dark mode");
    }

    // Add theme indicator
    const indicator = this.getThemeIndicator();
    button.setAttribute("title", `Current: ${indicator}`);
  }

  getThemeIndicator() {
    switch (this.currentTheme) {
      case "light":
        return "Light Mode";
      case "dark":
        return "Dark Mode";
      case "auto":
        return "Auto (follows system)";
      default:
        return "Auto";
    }
  }

  saveTheme() {
    localStorage.setItem("ai-events-theme", this.currentTheme);
  }

  async syncWithServer() {
    if (!ai_events_public || !ai_events_public.ajax_url) return;

    try {
      const formData = new FormData();
      formData.append("action", "toggle_theme_mode");
      formData.append("nonce", ai_events_public.nonce);
      formData.append("mode", this.currentTheme);

      await fetch(ai_events_public.ajax_url, {
        method: "POST",
        body: formData,
      });
    } catch (error) {
      console.warn("Failed to sync theme preference with server:", error);
    }
  }

  // Public method to set theme programmatically
  setTheme(theme) {
    if (["light", "dark", "auto"].includes(theme)) {
      this.currentTheme = theme;
      this.saveTheme();
      this.applyTheme();
      this.syncWithServer();
    }
  }

  // Get current theme
  getTheme() {
    return this.currentTheme;
  }

  // Get effective theme (resolves 'auto' to actual theme)
  getEffectiveTheme() {
    if (this.currentTheme === "auto") {
      return window.matchMedia &&
        window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
    }
    return this.currentTheme;
  }
}

// Initialize theme switcher
document.addEventListener("DOMContentLoaded", function () {
  window.aiEventsTheme = new AIEventsThemeSwitcher();
});

// Expose theme switcher globally for potential integrations
window.AIEventsThemeSwitcher = AIEventsThemeSwitcher;
