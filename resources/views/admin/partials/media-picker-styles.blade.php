<style>
    .media-picker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: .75rem; max-height: 360px; overflow: auto; }
    .media-picker-item { border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .35rem; cursor: pointer; background: #fff; text-align: center; }
    .media-picker-item.is-selected, .media-picker-item:hover { border-color: var(--bs-primary); box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), .15); }
    .media-picker-item img { width: 100%; height: 80px; object-fit: cover; border-radius: .35rem; }
    .featured-preview { border-radius: .5rem; object-fit: cover; max-height: 220px; }
</style>
