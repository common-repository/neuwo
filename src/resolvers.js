import { fetchData } from './controls'
import { populateData } from './actions'  // TODO move actions to file

// Dispatching Control Action via Control Action Creator, pauses till resolves.
export function* getData(){
    const postId = yield wp.data.select('core/editor').getCurrentPostId();
    const apiData = yield fetchData(postId);
    if (apiData === ""){
        console.log("Neuwo data not set.")
        return;
    }
    return populateData(apiData);
}
