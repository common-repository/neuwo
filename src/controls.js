import apiFetch from '@wordpress/api-fetch';

// "Control Actions" Required for resolver to send API request
// Defines execution flow by specific type of action

// Control Action Creator, when dispatched runs control function.
export const fetchData = (postId) => {
    return {
        type: 'FETCH_DATA',
        postId
    }
}

// Control Function, returns a promise
export default {
    FETCH_DATA({postId}) {
        const resp = apiFetch( { path: '/neuwo/v1/getData?postId=' + postId } ).then( ( response ) => response );
        return resp;
    }
}