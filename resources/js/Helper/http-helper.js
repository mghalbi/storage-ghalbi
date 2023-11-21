export function httpGet(url) {
    return fetch(url, {
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
        },
    }).then((response) => response.json());
}
