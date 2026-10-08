(() => {
    const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
    const actionHeader = /^(aksi|action|actions)$/i;

    function parseDate(value) {
        let match = value.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/);
        if (match) {
            return Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
        }

        match = value.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$/);
        if (match) {
            return Date.UTC(Number(match[3]), Number(match[2]) - 1, Number(match[1]));
        }

        return null;
    }

    function parseNumber(value) {
        let normalized = value.replace(/[^\d,.-]/g, '');
        if (!normalized || !/^-?[\d.,]+$/.test(normalized)) {
            return null;
        }

        const lastComma = normalized.lastIndexOf(',');
        const lastPeriod = normalized.lastIndexOf('.');
        const decimalSeparator = Math.max(lastComma, lastPeriod);
        const hasBothSeparators = lastComma !== -1 && lastPeriod !== -1;

        if (hasBothSeparators) {
            const decimals = normalized.length - decimalSeparator - 1;
            normalized = normalized
                .replace(/[,.]/g, (separator, offset) =>
                    offset === decimalSeparator && decimals > 0 ? '.' : '');
        } else if (decimalSeparator !== -1) {
            const separators = normalized.match(/[,.]/g).length;
            const decimals = normalized.length - decimalSeparator - 1;
            if (separators > 1 || decimals === 3) {
                normalized = normalized.replace(/[,.]/g, '');
            } else {
                normalized = normalized.replace(/[,.]/g, '.');
            }
        }

        const number = Number(normalized);
        return Number.isFinite(number) ? number : null;
    }

    function getSortValue(cell) {
        const value = (cell.dataset.sortValue ?? cell.textContent).trim();
        return {
            text: value,
            date: parseDate(value),
            number: parseNumber(value),
        };
    }

    function compareCells(leftCell, rightCell, direction) {
        const left = getSortValue(leftCell);
        const right = getSortValue(rightCell);

        if (!left.text || !right.text) {
            return left.text ? -1 : right.text ? 1 : 0;
        }

        let result;
        if (left.date !== null && right.date !== null) {
            result = left.date - right.date;
        } else if (left.number !== null && right.number !== null) {
            result = left.number - right.number;
        } else {
            result = collator.compare(left.text, right.text);
        }

        return result * direction;
    }

    function isActionHeader(header) {
        return actionHeader.test(header.textContent.trim());
    }

    function enhanceTable(table) {
        if (table.dataset.sortableEnhanced === 'true'
            || table.dataset.sortable === 'false'
            || !table.matches('table.table')) {
            return;
        }

        const headerRow = table.tHead?.rows[table.tHead.rows.length - 1];
        if (!headerRow) {
            return;
        }

        table.dataset.sortableEnhanced = 'true';

        Array.from(headerRow.cells).forEach((header) => {
            if (header.tagName !== 'TH'
                || header.colSpan > 1
                || header.querySelector('a, button, input, select, textarea')
                || isActionHeader(header)) {
                return;
            }

            header.classList.add('table-sortable-header');
            header.tabIndex = 0;
            header.setAttribute('aria-sort', 'none');

            const indicator = document.createElement('span');
            indicator.className = 'table-sort-indicator';
            indicator.setAttribute('aria-hidden', 'true');
            indicator.textContent = '↕';
            header.append(indicator);

            const sort = () => {
                const previousDirection = header.getAttribute('aria-sort');
                const direction = previousDirection === 'ascending' ? -1 : 1;
                const sortOrder = direction === 1 ? 'ascending' : 'descending';
                const columnIndex = header.cellIndex;

                table.tHead.querySelectorAll('.table-sortable-header').forEach((cell) => {
                    cell.setAttribute('aria-sort', 'none');
                    cell.querySelector('.table-sort-indicator').textContent = '↕';
                });

                header.setAttribute('aria-sort', sortOrder);
                indicator.textContent = direction === 1 ? '↑' : '↓';

                Array.from(table.tBodies).forEach((body) => {
                    const rows = Array.from(body.rows);
                    const sortableRows = [];
                    const otherRows = [];

                    rows.forEach((row, index) => {
                        const cell = row.cells[columnIndex];
                        if (cell && cell.colSpan === 1) {
                            sortableRows.push({ row, cell, index });
                        } else {
                            otherRows.push({ row, index });
                        }
                    });

                    [...sortableRows, ...otherRows]
                        .sort((left, right) => {
                            if (left.cell && right.cell) {
                                return compareCells(left.cell, right.cell, direction) ||
                                    left.index - right.index;
                            }
                            if (left.cell) return -1;
                            if (right.cell) return 1;
                            return left.index - right.index;
                        })
                        .forEach(({ row }) => body.append(row));
                });
            };

            header.addEventListener('click', sort);
            header.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    sort();
                }
            });
        });
    }

    function enhanceTables(root = document) {
        if (root instanceof HTMLTableElement) {
            enhanceTable(root);
            return;
        }

        root.querySelectorAll?.('table.table').forEach(enhanceTable);
    }

    enhanceTables();

    new MutationObserver((mutations) => {
        mutations.forEach(({ addedNodes }) => {
            addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    enhanceTables(node);
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
