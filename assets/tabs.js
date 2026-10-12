(function () {
  'use strict';
  const tabList = document.getElementById('app-tabs');
  const tabs = Array.from(tabList.querySelectorAll('[role="tab"]'));
  const panels = Array.from(document.querySelectorAll('[role="tabpanel"]'));
  const aliases = new Map([['results-title', 'search'], ['saved-panel', 'saved'], ['discovery-title', 'discovery']]);

  function tabNameFromUrl() {
    const hash = window.location.hash.slice(1);
    const name = aliases.get(hash) || hash;
    return tabs.some(function (tab) { return tab.dataset.openTab === name; }) ? name : 'search';
  }

  function activate(name, focus) {
    const selected = tabs.find(function (tab) { return tab.dataset.openTab === name; });
    if (!selected) return;
    const selectedPanel = selected.getAttribute('aria-controls');
    const focusWasHidden = panels.some(function (panel) { return panel.id !== selectedPanel && panel.contains(document.activeElement); });
    tabs.forEach(function (tab) {
      const active = tab === selected;
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
    });
    panels.forEach(function (panel) { panel.hidden = panel.id !== selectedPanel; });
    document.getElementById('explore-tools').hidden = name !== 'search' && name !== 'saved';
    document.title = selected.textContent.trim() + ' — NhomNhom';
    if (focus || focusWasHidden || tabList.contains(document.activeElement)) selected.focus({ preventScroll: true });
  }

  function navigate(name) {
    if (!tabs.some(function (tab) { return tab.dataset.openTab === name; })) return;
    if (window.location.hash !== '#' + name) window.history.pushState(null, '', '#' + name);
    activate(name, true);
    window.scrollTo({ top: 0, behavior: 'instant' });
    tabs.find(function (tab) { return tab.dataset.openTab === name; }).scrollIntoView({ block: 'nearest', inline: 'nearest' });
  }

  document.addEventListener('click', function (event) {
    const link = event.target.closest('[data-open-tab]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    event.preventDefault();
    navigate(link.dataset.openTab);
  });

  tabList.addEventListener('keydown', function (event) {
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    const current = tabs.indexOf(event.target.closest('[role="tab"]'));
    if (current < 0) return;
    let next;
    if (event.key === 'ArrowRight') next = (current + 1) % tabs.length;
    else if (event.key === 'ArrowLeft') next = (current + tabs.length - 1) % tabs.length;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = tabs.length - 1;
    else if (event.key === ' ') next = current;
    else return;
    event.preventDefault();
    navigate(tabs[next].dataset.openTab);
  });

  window.addEventListener('popstate', function () { activate(tabNameFromUrl(), false); });
  window.addEventListener('hashchange', function () { activate(tabNameFromUrl(), false); });
  activate(tabNameFromUrl(), false);
}());
