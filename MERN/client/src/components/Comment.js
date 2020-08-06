import React, { useState, useEffect } from 'react';
import { connect } from "react-redux";
import axios from 'axios';
import moment from 'moment';
import config from '../config';
import { getUsername } from '../redux/user/selector';

function Comment(props) {
    const [formData, setFormData] = useState({body: ''});
    const [comments, setComments] = useState([]);

    useEffect(() => {
        axios.get(config.api_url + '/comment')
            .then(res => setComments(res.data))
            .catch(err => console.log(err));
    }, []);

    const bodyInputHandler = e => {
        setFormData({...formData, body: e.target.value});
    }

    const createComment = e => {
        e.preventDefault();

        axios.post(config.api_url + '/comment/add', {author: props.username, body: formData.body})
            .then(res => {
                setComments([res.data, ...comments]);
                setFormData({...formData, body: ''});
            })
            .catch(err => console.log(err));
    }

    return (
        <div>
            {props.username}
            <div className="comments">
                <form onSubmit={createComment}>
                    <textarea value={formData.body} onChange={bodyInputHandler}></textarea>
                    <button type="submit">Send</button>
                </form>
                {comments.map(comment => (
                    <div className="comment" key={comment._id}>
                        <div className="comment-header">
                            <b>{comment.author}</b> - <i>{moment(comment.created_at).format('Y-MM-DD H:mm')}</i>
                        </div>
                        <div className="comment-body">{comment.body}</div>
                    </div>
                ))}
            </div>
        </div>
    )
}

export default connect(state => ({
    username: getUsername(state)
}))(Comment);