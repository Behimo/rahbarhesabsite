<style>
    .sb-choice, .sb-pill, .sb-page, .sb-segment label { margin: 0; cursor: pointer; }
    .sb-choice input, .sb-pill input, .sb-page input, .sb-segment input { position: absolute; opacity: 0; pointer-events: none; }
    .sb-choice { position: relative; display: flex; flex-direction: column; gap: .2rem; height: 100%; padding: .9rem 1rem; border: 1px solid var(--bs-border-color); border-radius: .75rem; background: var(--bs-paper-bg, #fff); }
    .sb-choice:has(input:checked) { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .08); }
    .sb-choice:focus-within, .sb-pill:focus-within, .sb-page:focus-within, .sb-segment label:focus-within { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
    .sb-choice__title, .sb-pill span, .sb-segment span { font-weight: 600; }
    .sb-choice__hint { color: var(--bs-secondary-color); font-size: .8125rem; line-height: 1.55; }
    .sb-choice__title { display: inline-flex; align-items: center; gap: .45rem; }
    .sb-swatch { width: .75rem; height: .75rem; border-radius: 999px; display: inline-block; }
    .sb-swatch--orange { background: #f59e0b; }
    .sb-swatch--purple { background: #7367f0; }
    .sb-swatch--blue { background: #00cfe8; }
    .sb-swatch--green { background: #28c76f; }
    .sb-pills { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .sb-pills--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sb-pill, .sb-page, .sb-segment label { position: relative; }
    .sb-pill span, .sb-page span, .sb-segment span { display: flex; align-items: center; justify-content: center; gap: .4rem; min-height: 2.5rem; padding: .35rem .75rem; border: 1px solid var(--bs-border-color); border-radius: .65rem; background: var(--bs-paper-bg, #fff); text-align: center; line-height: 1.4; }
    .sb-pill:has(input:checked) span, .sb-page:has(input:checked) span { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); }
    .sb-page span { justify-content: flex-start; min-height: 2.15rem; border-radius: 999px; font-size: .875rem; font-weight: 500; }
    .sb-segment { display: grid; grid-template-columns: 1fr 1fr; gap: .35rem; margin-bottom: 1rem; padding: .25rem; border-radius: .8rem; background: rgba(var(--bs-secondary-rgb), .1); }
    .sb-segment span { border-color: transparent; background: transparent; }
    .sb-segment label:has(input:checked) span { border-color: var(--bs-border-color); background: var(--bs-paper-bg, #fff); color: var(--bs-primary); }
    .sb-pages-scroll { max-height: 28rem; overflow: auto; padding-inline-end: .15rem; }
    .sb-page-group + .sb-page-group { margin-top: .9rem; }
    .sb-page-group__title { margin-bottom: .45rem; color: var(--bs-secondary-color); font-size: .75rem; font-weight: 700; }
    .sb-page-grid { display: flex; flex-wrap: wrap; gap: .4rem; }
    .sb-count { color: var(--bs-primary); font-size: .8125rem; font-weight: 600; white-space: nowrap; }
    .sb-summary { margin: 0 0 1rem; color: var(--bs-secondary-color); font-size: .875rem; line-height: 1.6; }
    .sb-page-empty { margin: .75rem 0 0; color: var(--bs-secondary-color); font-size: .875rem; }
    @media (max-width: 575.98px) {
        .sb-pills, .sb-pills--2 { grid-template-columns: 1fr; }
    }
</style>
