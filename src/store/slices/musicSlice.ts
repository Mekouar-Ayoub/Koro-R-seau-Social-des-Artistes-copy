import { createSlice } from '@reduxjs/toolkit';

interface MusicState {
  currentTrack: any | null;
  isPlaying: boolean;
  volume: number;
  currentTime: number;
  duration: number;
}

const initialState: MusicState = {
  currentTrack: null,
  isPlaying: false,
  volume: 0.8,
  currentTime: 0,
  duration: 0,
};

const musicSlice = createSlice({
  name: 'music',
  initialState,
  reducers: {
    setCurrentTrack: (state, action) => {
      state.currentTrack = action.payload;
    },
    togglePlay: (state) => {
      state.isPlaying = !state.isPlaying;
    },
    setVolume: (state, action) => {
      state.volume = action.payload;
    },
    setCurrentTime: (state, action) => {
      state.currentTime = action.payload;
    },
    setDuration: (state, action) => {
      state.duration = action.payload;
    },
  },
});

export const { setCurrentTrack, togglePlay, setVolume, setCurrentTime, setDuration } = musicSlice.actions;
export default musicSlice.reducer;
