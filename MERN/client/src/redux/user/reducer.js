import actionTypes from './actionTypes';

const initialState = {
    user: {
        name: 'Tomi'
    }
}

export default function (state = initialState, action) {
    switch (action.type) {
        case actionTypes.SET_USER:
            console.log('SET USER', action);
            return {
                ...state.user,
                user: {
                    name: action.payload
                }
            };
        default: return state;
    }
}