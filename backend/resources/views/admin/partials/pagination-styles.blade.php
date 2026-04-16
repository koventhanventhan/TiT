<style>
    /* Custom Pagination Styles */
    .pagination-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; /* allow wrapping on small screens */
        gap: 1rem;
        background: #3b3363;/* Dark background */
        padding: 0.9375rem 1.5625rem;
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
        color: #9ca3af;
        font-size: 0.875rem;
        margin: -1.5625rem -1.875rem -1.5625rem -1.875rem; /* Offset card padding */
        border-top: 1.0px solid #ffffff;
    }

    .pagination-info {
        flex: 1 1 100%;
        text-align: center;
    }

    @media (min-width: 768px) {
        .pagination-info {
            flex: 1 1 auto;
            text-align: left;
        }
    }

    .pagination-per-page {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.625rem;
    }

    .pagination-per-page select {
        background: #1f2937;
        border: 1.0px solid #374151;
        color: #fff;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        cursor: pointer;
        outline: none;
    }

    .custom-pagination-container {
        display: flex;
        justify-content: flex-start;
        border: 1.0px solid #374151;
        border-radius: 0.375rem;
        overflow-x: auto;
        /* Custom scrollbar for container */
        scrollbar-width: thin;
        scrollbar-color: #EB8153 transparent;
        width: 100%;
    }
    @media (min-width: 768px) {
        .custom-pagination-container {
            width: auto;
            margin-left: auto;
        }
    }

    .pagination-item {
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        text-decoration: none;
        border-right: 1.0px solid #374151;
        background: #1f2937;
        transition: all 0.2s;
        min-width: 2.5rem;
    }

    .pagination-item:last-child {
        border-right: none;
    }

    .pagination-item:hover:not(.disabled):not(.active) {
        background: #374151;
        color: #fff;
        text-decoration: none;
    }

    .pagination-item.active {
        color: #fbbf24; /* Active page color (orange/yellow) */
        font-weight: 600;
        background: #1f2937;
    }

    .pagination-item.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
