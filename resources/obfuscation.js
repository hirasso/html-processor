"use strict";

// @ts-check

class ObfuscatedElement extends HTMLElement {
  connectedCallback() {
    const value = atob(this.getAttribute("value") ?? '');
    const key = this.getAttribute("key");

    if (!value || !key) {
      console.error("No value or key provided, destroying...");
      this.remove();
      return;
    }

    let result = "";
    for (let i = 0; i < value.length; i++)
      result += String.fromCharCode(
        value.charCodeAt(i) ^ key.charCodeAt(i % key.length),
      );

    console.log(result);

    this.replaceWith(result);
  }

  /**
   * @param {string} data
   * @param {string} key
   * @return {string}
   */
  decode(data, key) {
    let out = "";
    for (let i = 0; i < data.length; i++)
      out += String.fromCharCode(
        data.charCodeAt(i) ^ key.charCodeAt(i % key.length),
      );
    return out;
  }
}

if (!window.customElements.get("html-processor-obfuscated")) {
  window.customElements.define("html-processor-obfuscated", ObfuscatedElement);
}
