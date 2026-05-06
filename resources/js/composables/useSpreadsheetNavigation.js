import { nextTick, reactive } from 'vue';

export const spreadsheetColumns = ['company', 'date', 'employee', 'project', 'task', 'hours'];

export function useSpreadsheetNavigation(rowCount, addRow) {
    const activeCell = reactive({
        row: 0,
        column: 0,
    });
    const cellRefs = new Map();

    function cellKey(row, column) {
        return `${row}:${column}`;
    }

    function setCellRef(row, column, element) {
        const key = cellKey(row, column);

        if (element) {
            cellRefs.set(key, element);
            return;
        }

        cellRefs.delete(key);
    }

    async function focusCell(row, column) {
        await nextTick();

        const target = cellRefs.get(cellKey(row, column));

        if (typeof target?.focus === 'function') {
            target.focus();
            return;
        }

        if (typeof target?.$el?.focus === 'function') {
            target.$el.focus();
            return;
        }

        target?.$el?.querySelector?.('button,input,[tabindex]')?.focus();
    }

    function setActiveCell(row, column, options = {}) {
        activeCell.row = Math.max(0, Math.min(row, rowCount.value - 1));
        activeCell.column = Math.max(0, Math.min(column, spreadsheetColumns.length - 1));

        if (options.focus) {
            focusCell(activeCell.row, activeCell.column);
        }
    }

    function handleCellKeydown(event, row, column) {
        if (['Enter', ' ', 'F2'].includes(event.key) && event.currentTarget.dataset.editor === 'trigger') {
            return;
        }

        if (!['Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
            return;
        }

        event.preventDefault();

        if (event.key === 'Tab') {
            const direction = event.shiftKey ? -1 : 1;
            const nextColumn = column + direction;

            if (nextColumn >= spreadsheetColumns.length) {
                if (row === rowCount.value - 1) {
                    addRow();
                }
                setActiveCell(row + 1, 0, { focus: true });
                return;
            }

            if (nextColumn < 0) {
                setActiveCell(row - 1, spreadsheetColumns.length - 1, { focus: true });
                return;
            }

            setActiveCell(row, nextColumn, { focus: true });
            return;
        }

        if (event.key === 'Enter') {
            setActiveCell(row + 1, column, { focus: true });
            return;
        }

        const movements = {
            ArrowLeft: [0, -1],
            ArrowRight: [0, 1],
            ArrowUp: [-1, 0],
            ArrowDown: [1, 0],
        };
        const [rowDelta, columnDelta] = movements[event.key];
        setActiveCell(row + rowDelta, column + columnDelta, { focus: true });
    }

    return {
        activeCell,
        handleCellKeydown,
        setCellRef,
        setActiveCell,
    };
}
