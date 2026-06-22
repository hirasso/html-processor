"use strict";

// @ts-check

(function () {
  const seen = new WeakSet();

  /** @param {string} attr */
  function processElements(attr = "data-html-processor-obfuscated") {
    document.querySelectorAll(`a[${attr}],span[${attr}]`).forEach((el) => {
      if (seen.has(el)) return;
      seen.add(el);

      const encoded = el.getAttribute(attr) ?? "";
      const isTel = encoded.match(/^\/[\d\s\+]+\/[\d\s\+]+\//);
      const decoded = isTel
        ? encoded.split("/").reverse().join("")
        : encoded
            .split("/")
            .map((p) => p.split("").reverse().join(""))
            .join("@");

      if (el instanceof HTMLAnchorElement) {
        el.setAttribute("href", isTel ? `tel:${decoded}` : `mailto:${decoded}`);
        el.removeAttribute(attr);
      }
      if (el instanceof HTMLSpanElement) {
        el.replaceWith(decoded);
      }
    });
  }

  function processComments() {
    const walker = document.createTreeWalker(
      document.body,
      NodeFilter.SHOW_COMMENT,
    );

    const comments = [];
    while (walker.nextNode()) {
      if (walker.currentNode.textContent?.startsWith("html-processor:")) {
        comments.push(walker.currentNode);
      }
    }

    comments.forEach((comment) => {
      const encoded = comment.textContent?.match(/html-processor:(.*)$/)?.[1];
      if (!encoded) return;

      const isTel = encoded.match(/^\/[\d\s\+]+\/[\d\s\+]+\//);
      const decoded = isTel
        ? encoded.split("/").reverse().join("")
        : encoded
            .split("/")
            .map((p) => p.split("").reverse().join(""))
            .join("@");

      comment.parentNode?.replaceChild(
        document.createTextNode(decoded),
        comment,
      );
    });
  }

  processElements();
  processComments();

  const observer = new MutationObserver(() => {
    processElements();
    processComments();
  });
  observer.observe(document.body, { childList: true, subtree: true });
})();
