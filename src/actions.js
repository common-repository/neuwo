export const addTag = (tag) => {
    return {
        type: 'ADD_TAG',
        tag
    }
};

export const populateData = (neuwoData) => {
    return {
        type: 'POPULATE_DATA',
        neuwoData
    }
};