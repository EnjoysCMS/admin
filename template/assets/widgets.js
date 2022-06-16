let grid = GridStack.init({
    cellHeight: 20,
    disableResize: true,
    disableDrag: true,
});


let allowEditGrid = document.getElementById("allowEditGrid");
let gsDiv = document.getElementById("gs");

allowEditGrid.addEventListener('click', function (e) {
    grid.enableMove(this.checked);
    grid.enableResize(this.checked);
    this.checked ? gsDiv.classList.add('editable') : gsDiv.classList.remove('editable')
});

grid.on('change', function (e, items) {
    fetch(grid.el.dataset.saveUrl, {
        method: 'POST', mode: 'cors', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        headers: {
            'Content-Type': 'application/json'
            // 'Content-Type': 'application/x-www-form-urlencoded',
        }, referrerPolicy: 'no-referrer', // no-referrer, *client
        body: JSON.stringify({
            "data": grid.save(false),
        })
    }).then(function (response) {
        if (response.status !== 200) {
            response.json()
                .then(function (data) {
                    toastr.error(data, 'Error', {timeOut: 2000})
                })
                .catch(function (err) {
                    toastr.error('Json Error: ' + err, 'Error', {timeOut: 2000})
                });
            return false;
        }
        response.json()
            .then(function (data) {
                toastr.success(data, 'Success', {timeOut: 2000})
            })
            .catch(function (err) {
                toastr.error('Json Error: ' + err, 'Error', {timeOut: 2000})
            });
    }).catch(function (err) {
        toastr.error('Fetch Error: ' + err, 'Error', {timeOut: 2000})
    });
});

document.querySelectorAll('.delete-widget')
    .forEach(function (item) {
        item.addEventListener('click', function (e) {
            fetch(item.dataset.url, {
                method: 'POST', mode: 'cors', // no-cors, *cors, same-origin
                cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                headers: {
                    'Content-Type': 'application/json'
                    // 'Content-Type': 'application/x-www-form-urlencoded',
                }, referrerPolicy: 'no-referrer', // no-referrer, *client
            }).then(function (response) {
                if (response.status !== 200) {
                    response.json()
                        .then(function (data) {
                            toastr.error(data, 'Error', {timeOut: 2000})
                        })
                        .catch(function (err) {
                            toastr.error('Json Error: ' + err, 'Error', {timeOut: 2000})
                        });
                    return false;
                }
                response.json()
                    .then(function (data) {
                        grid.removeWidget(item.closest('.grid-stack-item'));
                        toastr.success(data, 'Success', {timeOut: 2000})
                    })
                    .catch(function (err) {
                        toastr.error('Json Error: ' + err, 'Error', {timeOut: 2000})
                    });
            }).catch(function (err) {
                toastr.error('Fetch Error: ' + err, 'Error', {timeOut: 2000})
            });
        });

    });
