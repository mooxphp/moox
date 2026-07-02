export function pickFileAsDataUrl({
    accept,
    onLoad,
    onError
}) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = accept;

    input.onchange = (event) => {
        const file = event.target?.files?.[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (loadEvent) => {
            onLoad(loadEvent?.target?.result);
        };
        reader.onerror = () => {
            if (typeof onError === 'function') {
                onError();
            }
        };
        reader.readAsDataURL(file);
    };

    input.click();
}

export function pickFile({
    accept,
    onSelect
}) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = accept;

    input.onchange = (event) => {
        const file = event.target?.files?.[0];
        if (!file) {
            return;
        }

        if (typeof onSelect === 'function') {
            onSelect(file);
        }
    };

    input.click();
}
