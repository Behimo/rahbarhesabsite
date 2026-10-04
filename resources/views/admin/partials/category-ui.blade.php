<style>
    .cat-tree { display: flex; flex-direction: column; }
    .cat-node {
        display: flex;
        gap: .85rem;
        align-items: stretch;
        padding: .9rem 1.15rem;
        margin-inline-start: calc(var(--depth) * 1.35rem);
        border-bottom: 1px solid var(--bs-border-color);
    }
    .cat-node:last-child { border-bottom: 0; }
    .cat-node__rail {
        width: 3px;
        border-radius: 99px;
        background: rgba(var(--bs-primary-rgb), .45);
        flex: none;
    }
    .cat-node__main { min-width: 0; }
    .cat-path {
        direction: ltr;
        unicode-bidi: isolate;
        font-size: .8rem;
        color: var(--bs-secondary-color);
    }
    .cat-pick {
        display: flex;
        flex-direction: column;
        max-height: 16rem;
        overflow: auto;
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
    }
    .cat-pick__row {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        margin: 0;
        padding: .65rem .85rem;
        border-bottom: 1px solid var(--bs-border-color);
        cursor: pointer;
    }
    .cat-pick__row:last-child { border-bottom: 0; }
    .cat-pick__row:hover { background: rgba(var(--bs-primary-rgb), .06); }
    .cat-pick__row:has(:checked) { background: rgba(var(--bs-primary-rgb), .08); }
    .cat-pick__row:focus-within { outline: 2px solid rgba(var(--bs-primary-rgb), .55); outline-offset: -2px; }
    .cat-pick__row input { margin-top: .2rem; flex: none; }
    .cat-pick__name { display: block; line-height: 1.35; }
</style>
