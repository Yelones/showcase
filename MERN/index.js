const express = require('express');
const mongoose = require('mongoose');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }))
app.use(cors({
    origin: 'http://localhost:3000'
}));

const TodoRoutes = require('./routes/Todo');
const LotteryRoutes = require('./routes/Lottery');
const CommentRoutes = require('./routes/Comment');

mongoose.connect('mongodb+srv://user:CcXldAGze5kkHjqQ@cluster0-uqsgs.mongodb.net/test?retryWrites=true&w=majority', {useNewUrlParser: true, useUnifiedTopology: true});
const db = mongoose.connection;
db.on('error', console.error.bind(console, 'connection error:'));
db.once('open', function() {
    console.log('Connected to MongoDB');
});

app.use('/todo', TodoRoutes);
app.use('/lottery', LotteryRoutes);
app.use('/comment', CommentRoutes);


app.listen(5000, () => console.log(`App listening at http://localhost:5000`));