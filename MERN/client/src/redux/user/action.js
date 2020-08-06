import actionTypes from './actionTypes';

export const setUser = (state) => ({
    type: actionTypes.SET_USER,
    payload: state.name
})